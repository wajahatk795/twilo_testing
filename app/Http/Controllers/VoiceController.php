<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use App\Models\SurveyCall;
use OpenAI\Laravel\Facades\OpenAI as OpenAIClient;

class VoiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Personality Engine (OPTION C)
    |--------------------------------------------------------------------------
    | Makes the caller feel like a real human speaking.
    | Adds natural pauses, fillers, soft tone, and conversational flow.
    */
    private function humanize($text)
    {
        $fillers = [
            "okay, um, ",
            "alright, so ",
            "hmm, let me think, ",
            "yeah, sure, ",
            "got it, ",
            "okay, just a sec… ",
        ];

        $prefix = $fillers[array_rand($fillers)];
        return $prefix . $text;
    }


    /*
    |--------------------------------------------------------------------------
    | Incoming Call
    |--------------------------------------------------------------------------
    */
    public function incoming()
    {
        $resp = new VoiceResponse();
        $sid  = request('CallSid');

        SurveyCall::firstOrCreate(
            ['call_sid' => $sid],
            ['phone' => request('From')]
        );

        $resp->say(
            $this->humanize("Hi there! I'm Joanna. Thanks for picking up."),
            ['voice' => 'Polly.Joanna']
        );

        $resp->redirect(route('twilio.question', ['q' => 1, 'sid' => $sid]));

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }


    /*
    |--------------------------------------------------------------------------
    | Ask Question
    |--------------------------------------------------------------------------
    */
    public function question()
    {
        $q   = (int) request('q');
        $sid = request('sid');

        $resp = new VoiceResponse();

        $prompts = [
            1 => "Could you please tell me your full name?",
            2 => "Alright, now I just need your email. Please spell it out slowly, one character at a time.",
            3 => "And lastly, may I have your phone number, one digit at a time please?"
        ];

        $gather = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 20,
            'action' => route('twilio.handle', ['q' => $q, 'sid' => $sid]),
            'method' => 'POST',
        ]);

        $gather->say(
            $this->humanize($prompts[$q]),
            ['voice' => 'Polly.Joanna']
        );

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }


    /*
    |--------------------------------------------------------------------------
    | Handle Speech Capture
    |--------------------------------------------------------------------------
    */
    public function handle(Request $request)
    {
        $resp = new VoiceResponse();

        try {
            $q      = (int) $request->input('q');
            $sid    = $request->input('sid');
            $speech = trim((string) $request->input('SpeechResult'));

            \Log::info(" RAW SPEECH", compact('sid', 'q', 'speech'));

            SurveyCall::where('call_sid', $sid)->update(["q{$q}_speech" => $speech]);

            if (!$speech) {
                $resp->say($this->humanize("Hmm, I didn't quite catch that. Let's give it another try."));
                $resp->redirect(route('twilio.question', compact('q', 'sid')));
                return response($resp, 200)->header('Content-Type', 'text/xml');
            }

            $answer = $speech;

            if ($q === 2) {
                $clean = strtolower($speech);
                $clean = str_replace([' ', ','], '', $clean);
                $clean = str_replace(['dot'], '.', $clean);
                $clean = str_replace(['at'], '@', $clean);
                $answer = preg_replace('/[^a-z0-9@._-]/', '', $clean);
            }

            if ($q === 3) {
                $digits = preg_replace('/\D/', '', $speech);
                $answer = implode(' ', str_split($digits));
            }

            $confirmText = "You said: $answer. Is that right? Just say yes or no.";

            $resp->gather([
                'input' => 'speech',
                'speechTimeout' => 'auto',
                'method' => 'POST',
                'action' => route('twilio.confirm', [
                    'q' => $q,
                    'sid' => $sid,
                    'answer' => urlencode($answer)
                ])
            ])->say($this->humanize($confirmText));

            return response($resp, 200)->header('Content-Type', 'text/xml');

        } catch (\Throwable $e) {
            \Log::error(' HANDLE ERROR: ' . $e->getMessage());
            $resp->say($this->humanize("Oh no, something went wrong. Let’s end this call for now."));
            $resp->hangup();
            return response($resp, 200)->header('Content-Type', 'text/xml');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Confirmation Step
    |--------------------------------------------------------------------------
    */
    public function confirm(Request $request)
    {
        $resp = new VoiceResponse();

        $q      = (int) $request->input('q');
        $sid    = $request->input('sid');
        $answer = urldecode($request->input('answer'));
        $speech = strtolower(trim($request->input('SpeechResult')));

        if (str_contains($speech, 'yes')) {

            if ($q === 3) {
                $cleanPhone = preg_replace('/\D/', '', $answer);
                if (strlen($cleanPhone) < 7) {
                    $resp->say($this->humanize("Hmm, that number didn't seem valid. Let's try again."));
                    $resp->redirect(route('twilio.question', ['q' => 3, 'sid' => $sid]));
                    return response($resp, 200);
                }
                $answer = $cleanPhone;
            }

            if ($q === 2 && !filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                $resp->say($this->humanize("That email doesn’t look right. Let’s try one more time."));
                $resp->redirect(route('twilio.question', ['q' => 2, 'sid' => $sid]));
                return response($resp, 200);
            }

            $call = SurveyCall::where('call_sid', $sid)->first();
            if ($call) {
                match ($q) {
                    1 => $call->update(['name' => $answer]),
                    2 => $call->update(['email' => $answer]),
                    3 => $call->update(['mobile' => $answer, 'status' => 'complete']),
                };
            }

            if ($q < 3) {
                $resp->say($this->humanize("Perfect, thank you!"));
                $resp->redirect(route('twilio.question', ['q' => $q + 1, 'sid' => $sid]));
            } else {
                $resp->say($this->humanize("That’s everything I needed. Thanks so much! Have a wonderful day."));
                $resp->hangup();
            }

        } else {
            $resp->say($this->humanize("No worries, let’s try that again."));
            $resp->redirect(route('twilio.question', compact('q', 'sid')));
        }

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }


    /*
    |--------------------------------------------------------------------------
    | OUTBOUND CALL (You asked where it went — here it is!)
    |--------------------------------------------------------------------------
    */
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

        return back()->with('status', 'Call placed successfully');
    }


    /*
    |--------------------------------------------------------------------------
    | OpenAI Test Route
    |--------------------------------------------------------------------------
    */
    public function openaiTest()
    {
        try {
            $result = OpenAIClient::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say hello in one word']
                ],
                'max_tokens' => 5
            ]);

            return response()->json([
                'status' => 'OpenAI OK',
                'reply'  => $result->choices[0]->message->content
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'OpenAI ERROR',
                'error'  => $e->getMessage()
            ], 500);
        }
    }
}
