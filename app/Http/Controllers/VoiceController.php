<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use App\Models\SurveyCall;
use OpenAI\Laravel\Facades\OpenAI as OpenAIClient;

class VoiceController extends Controller
{
    public function incoming()
    {
        $resp = new VoiceResponse();
        $sid  = request('CallSid');

        SurveyCall::firstOrCreate(
            ['call_sid' => $sid],
            ['phone' => request('From')]
        );

        $resp->say('Hi, we will ask you three quick questions.', ['voice' => 'Polly.Joanna']);
        $resp->redirect(route('twilio.question', ['q' => 1, 'sid' => $sid]));

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    public function question()
    {
        $q   = (int) request('q');
        $sid = request('sid');

        $resp = new VoiceResponse();

        $prompts = [
            1 => 'Please say your full name after the beep.',
            2 => 'Please spell your email address.',
            3 => 'Please say your date of birth, for example March 14 1988.',
        ];

        $gather = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 8,
            'action' => route('twilio.handle', ['q' => $q, 'sid' => $sid]),
            'method' => 'POST',
        ]);

        $gather->say($prompts[$q], ['voice' => 'Polly.Joanna']);

        //  NO fallback here — Twilio will return to handle()

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    public function handle(\Illuminate\Http\Request $request)
    {
        $resp = new \Twilio\TwiML\VoiceResponse();

        try {
            $q      = (int) $request->input('q');
            $sid    = $request->input('sid');
            $speech = trim((string) $request->input('SpeechResult'));

            if (!$speech) {
                $resp->say("I did not hear anything. Let's try again.");
                $resp->redirect(route('twilio.question', ['q' => $q, 'sid' => $sid]));
                return response($resp, 200)->header('Content-Type', 'text/xml');
            }

            //  OpenAI in SAFE MODE (cannot exceed size)
            $res = OpenAIClient::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0,
                'max_tokens' => 20,
                'messages' => [
                    ['role' => 'system', 'content' => 'Return only the extracted value.'],
                    ['role' => 'user', 'content' => "Question $q speech: $speech"]
                ],
            ]);

            $answer = substr(trim($res->choices[0]->message->content ?? 'unknown'), 0, 50);

            $call = SurveyCall::where('call_sid', $sid)->first();

            if ($call) {
                match ($q) {
                    1 => $call->update(['name' => $answer]),
                    2 => $call->update(['email' => $answer]),
                    3 => $call->update(['dob' => $answer, 'status' => 'complete']),
                };
            }

            if ($q < 3) {
                $resp->redirect(route('twilio.question', ['q' => $q + 1, 'sid' => $sid]));
            } else {
                $resp->say('Thank you, we have everything we need. Goodbye.');
                $resp->hangup();
            }

        } catch (\Throwable $e) {
            //  CRITICAL: Never allow Laravel to output HTML
            \Log::error('TWILIO HANDLE ERROR: ' . $e->getMessage());

            $resp->say("A system error occurred. Goodbye.");
            $resp->hangup();
        }

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    public function outbound($phone)
    {
        $twilio = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));

        $call = $twilio->calls->create(
            $phone,
            env('TWILIO_NUMBER'),
            ['url' => route('twilio.incoming')]
        );

        SurveyCall::create([
            'phone' => $phone,
            'call_sid' => $call->sid
        ]);

        return back()->with('status', 'Call placed');
    }

    public function openaiTest()
    {
        try {
            $result = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0,
                'messages' => [
                    ['role' => 'user', 'content' => 'Say hello in one word']
                ],
                'max_tokens' => 5,
            ]);

            return response()->json([
                'status' => ' OpenAI WORKING',
                'reply'  => $result->choices[0]->message->content,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => ' OpenAI FAILED',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

}
