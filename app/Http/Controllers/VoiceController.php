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

        // Professional AI greeting flow
        $gather = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 5, // wait 5 seconds for user response
            'action' => route('twilio.greeting', ['sid' => $sid, 'attempt' => 1]),
            'method' => 'POST',
        ]);

        $gather->say("Hello, I am Polly Joanna.", ['voice' => 'Polly.Joanna']);

        $resp->redirect(route('twilio.greeting', ['sid' => $sid, 'attempt' => 1]));

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    public function greeting(Request $request)
    {
        $resp = new VoiceResponse();
        $sid = $request->input('sid');
        $attempt = (int) $request->input('attempt', 1);
        $speech = strtolower(trim((string) $request->input('SpeechResult')));

        if ($speech && str_contains($speech, 'hello')) {
            // User responded, proceed to first question
            $resp->say("Great! Let's get started with a few quick questions.", ['voice' => 'Polly.Joanna']);
            $resp->redirect(route('twilio.question', ['q' => 1, 'sid' => $sid]));
        } else {
            if ($attempt < 3) {
                // Repeat greeting politely
                $gather = $resp->gather([
                    'input' => 'speech',
                    'speechTimeout' => 'auto',
                    'timeout' => 5,
                    'action' => route('twilio.greeting', ['sid' => $sid, 'attempt' => $attempt + 1]),
                    'method' => 'POST',
                ]);

                $message = match($attempt) {
                    1 => "Hello?",
                    2 => "Hello? I am Polly Joanna.",
                };

                $gather->say($message, ['voice' => 'Polly.Joanna']);
            } else {
                // End call after 3 failed attempts
                $resp->say("Since we did not hear from you, we will end the call. Goodbye.", ['voice' => 'Polly.Joanna']);
                $resp->hangup();
            }
        }

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    public function question()
    {
        $q   = (int) request('q');
        $sid = request('sid');

        $resp = new VoiceResponse();

        $prompts = [
            1 => 'Please say your full name after the beep.',
            2 => 'Please spell your email address, one character at a time. For example: a l e x dot j o h n s o n at g m a i l dot c o m',
            3 => 'Please say your phone number, one digit at a time. For example: 5 5 5 1 2 3 4 5 6 7',
        ];

        $gather = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 20,
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

            \Log::info("📢 RAW SPEECH", [
                'sid' => $sid,
                'q' => $q,
                'speech' => $speech,
            ]);

            // Save raw transcript
            SurveyCall::where('call_sid', $sid)
                ->update(["q{$q}_speech" => $speech]);

            if (!$speech) {
                $resp->say("I did not hear anything. Let's try again.");
                $resp->redirect(route('twilio.question', ['q' => $q, 'sid' => $sid]));
                return response($resp, 200)->header('Content-Type', 'text/xml');
            }

            $answer = $speech;

            // ------------------ Email reconstruction ------------------
            if ($q === 2) {
                $clean = strtolower($speech);
                $clean = str_replace([' ', ','], '', $clean); // remove spaces/commas
                $clean = str_replace(['dot'], '.', $clean);
                $clean = str_replace(['at'], '@', $clean);
                $answer = preg_replace('/[^a-z0-9@._-]/', '', $clean);
            }

            // ------------------ Phone reconstruction ------------------
            if ($q === 3) {
                $answer = preg_replace('/\D/', '', $speech); // only digits
            }

            if ($q === 3) {
                // Read phone numbers digit by digit
                $answer = implode(' ', str_split($answer));
            } else {
                $answer = $answer;
            }

            // -------------- Confirmation step ------------------
            $resp->gather([
                'input' => 'speech',
                'speechTimeout' => 'auto',
                'method' => 'POST',
                'action' => route('twilio.confirm', [
                    'q' => $q,
                    'sid' => $sid,
                    'answer' => urlencode($answer)
                ])
            ])->say("You said: $answer. Is that correct? Say Yes or No.");

            return response($resp, 200)->header('Content-Type', 'text/xml');

        } catch (\Throwable $e) {
            \Log::error('🔥 TWILIO HANDLE ERROR: ' . $e->getMessage());
            $resp->say("A system error occurred. Goodbye.");
            $resp->hangup();
            return response($resp, 200)->header('Content-Type', 'text/xml');
        }
    }

    public function confirm(Request $request)
    {
        $resp = new \Twilio\TwiML\VoiceResponse();

        $q      = (int) $request->input('q');
        $sid    = $request->input('sid');
        $answer = urldecode($request->input('answer'));
        $speech = strtolower(trim($request->input('SpeechResult')));

        \Log::info("🟦 CONFIRMATION", [
            'sid' => $sid,
            'q' => $q,
            'speech' => $speech,
            'answer' => $answer
        ]);

        if (str_contains($speech, 'yes')) {

            // PHONE VALIDATION (question 3)
            if ($q === 3) {
                $cleanPhone = preg_replace('/\D/', '', $answer);
                if (strlen($cleanPhone) < 7) {
                    $resp->say("The phone number you provided is not valid. Please try again.");
                    $resp->redirect(route('twilio.question', ['q' => 3, 'sid' => $sid]));
                    return response($resp, 200)->header('Content-Type', 'text/xml');
                }
                $answer = $cleanPhone;
            }

            // EMAIL VALIDATION (question 2)
            if ($q === 2 && !filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                $resp->say("The email you spoke is not valid. Let's try again.");
                $resp->redirect(route('twilio.question', ['q' => 2, 'sid' => $sid]));
                return response($resp, 200)->header('Content-Type', 'text/xml');
            }

            // Save confirmed answer
            $call = SurveyCall::where('call_sid', $sid)->first();
            if ($call) {
                match ($q) {
                    1 => $call->update(['name' => $answer]),
                    2 => $call->update(['email' => $answer]),
                    3 => $call->update(['mobile' => $answer, 'status' => 'complete']),
                };
            }

            // Move to next question automatically
            if ($q < 3) {
                $resp->redirect(route('twilio.question', [
                    'q' => $q + 1,
                    'sid' => $sid
                ]));
            } else {
                $resp->say("Thank you, all details recorded. Goodbye.");
                $resp->hangup();
            }

        } else {
            // user said no → repeat current question
            $resp->say("Okay, let's try again.");
            $resp->redirect(route('twilio.question', [
                'q' => $q,
                'sid' => $sid
            ]));
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
