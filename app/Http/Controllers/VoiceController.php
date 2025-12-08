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
            'speechTimeout' => 'auto',   // User can speak longer
            'timeout' => 15,             // Give them 15 seconds
            'action' => route('twilio.handle', ['q' => $q, 'sid' => $sid]),
            'method' => 'POST',
        ]);

        $gather->say($prompts[$q], ['voice' => 'Polly.Joanna']);

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    public function handle(Request $request)
    {
        $resp = new \Twilio\TwiML\VoiceResponse();

        try {
            $q      = (int) $request->input('q');
            $sid    = $request->input('sid');
            $speech = trim((string) $request->input('SpeechResult'));

            \Log::info("📞 [TWILIO HANDLE] Incoming Speech", [
                'question' => $q,
                'sid' => $sid,
                'SpeechResult_raw' => $request->input('SpeechResult'),
            ]);

            if (!$speech) {
                \Log::warning("⚠️ No speech detected for question $q", [
                    'sid' => $sid
                ]);

                $resp->say("I did not hear anything. Let's try again.");
                $resp->redirect(route('twilio.question', ['q' => $q, 'sid' => $sid]));
                return response($resp, 200)->header('Content-Type', 'text/xml');
            }

            // ----- OpenAI Extraction -----
            $res = OpenAIClient::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0,
                'max_tokens' => 30,
                'messages' => [
                    ['role' => 'system', 'content' => 'Return only the extracted value. No explanation.'],
                    ['role' => 'user', 'content' => "Q$q Speech: $speech"]
                ],
            ]);

            $answer = trim($res->choices[0]->message->content ?? '');

            \Log::info("🤖 OpenAI Extraction", [
                'question' => $q,
                'sid' => $sid,
                'speech_input' => $speech,
                'openai_answer' => $answer,
            ]);

            // ----- Email Validation -----
            if ($q === 2) {
                if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {

                    \Log::warning("❌ Email validation failed", [
                        'invalid_email' => $answer,
                        'sid' => $sid
                    ]);

                    $resp->say("The email you provided is not valid. Please try again.");
                    $resp->redirect(route('twilio.question', ['q' => 2, 'sid' => $sid]));
                    return response($resp, 200)->header('Content-Type', 'text/xml');
                }
            }

            // ----- Save to DB -----
            $call = SurveyCall::where('call_sid', $sid)->first();

            if ($call) {
                match ($q) {
                    1 => $call->update(['name' => $answer]),
                    2 => $call->update(['email' => $answer]),
                    3 => $call->update(['dob' => $answer, 'status' => 'complete']),
                };

                \Log::info("💾 Data saved to database", [
                    'q' => $q,
                    'sid' => $sid,
                    'saved_answer' => $answer,
                ]);
            }

            // ----- Move to Next Step -----
            if ($q < 3) {
                \Log::info("➡️ Moving to next question", [
                    'next_question' => $q + 1,
                    'sid' => $sid
                ]);

                $resp->redirect(route('twilio.question', ['q' => $q + 1, 'sid' => $sid]));
            } else {
                \Log::info("📞 Survey complete — hanging up", [
                    'sid' => $sid
                ]);

                $resp->say('Thank you. Goodbye.');
                $resp->hangup();
            }

        } catch (\Throwable $e) {
            \Log::error("🔥 TWILIO ERROR", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $resp->say('A system error occurred. Goodbye.');
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
                'status' => '✅ OpenAI WORKING',
                'reply'  => $result->choices[0]->message->content,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => '❌ OpenAI FAILED',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

}
