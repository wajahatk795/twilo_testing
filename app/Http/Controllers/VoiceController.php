<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use App\Models\SurveyCall;
use OpenAI\Laravel\Facades\OpenAI as OpenAIClient;

class VoiceController extends Controller
{
    // Incoming call: create record (or find) and start at step 1
    public function incoming(Request $request)
    {
        \Log::info('TWILIO INCOMING', $request->all());

        $sid  = $request->input('CallSid');
        $from = $request->input('From');

        $call = SurveyCall::firstOrCreate(
            ['call_sid' => $sid],
            [
                'phone' => $from,
                'status' => 'started',
                'current_step' => 1,
                'conversation' => json_encode([['role'=>'system','content'=>'lead-capture session']])
            ]
        );

        $resp = new VoiceResponse();
        $resp->say("Hi — thanks for taking this call. I have three quick questions.", ['voice' => 'Polly.Joanna']);
        $resp->pause(['length' => 1]);

        // Redirect to question route for the first step
        $resp->redirect(route('twilio.question', ['sid' => $sid]));
        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    // Show the current question for this call (one-by-one)
    public function question(Request $request)
    {
        $sid = $request->input('sid');
        $call = SurveyCall::where('call_sid', $sid)->first();

        if (!$call) {
            $resp = new VoiceResponse();
            $resp->say("Call record error. Goodbye.", ['voice'=>'Polly.Joanna']);
            $resp->hangup();
            return response($resp,200)->header('Content-Type','text/xml');
        }

        $step = (int)($call->current_step ?? 1);
        $resp = new VoiceResponse();

        // Prompts for each step
        $prompts = [
            1 => "Please tell me your full name after the beep.",
            2 => "Please say your phone number, one digit at a time. For example: five five five one two three four five six seven.",
            3 => "Please spell your email address slowly, one character at a time. For example: j o h n dot d o e at g m a i l dot c o m.",
        ];

        $prompt = $prompts[$step] ?? "Thank you.";

        $g = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 8,
            'action' => route('twilio.handle', ['sid' => $sid]),
            'method' => 'POST',
        ]);

        // Ask the step prompt inside gather so Twilio listens after speaking
        $g->say($prompt, ['voice' => 'Polly.Joanna']);

        // Fallback if nothing captured — repeat same question
        $resp->say("We didn't hear anything. Let's try again.", ['voice' => 'Polly.Joanna']);
        $resp->redirect(route('twilio.question', ['sid' => $sid]));

        return response($resp,200)->header('Content-Type','text/xml');
    }

    // Handle captured speech, use OpenAI to extract/normalize the single field for the current step
    public function handle(Request $request)
    {
        $sid    = $request->input('sid');
        $speech = trim((string)$request->input('SpeechResult', ''));
        \Log::info('TWILIO HANDLE', ['sid'=>$sid,'speech'=>$speech]);

        $call = SurveyCall::where('call_sid', $sid)->first();
        if (!$call) {
            $resp = new VoiceResponse();
            $resp->say("Call record missing. Goodbye.", ['voice'=>'Polly.Joanna']);
            $resp->hangup();
            return response($resp,200)->header('Content-Type','text/xml');
        }

        $step = (int)($call->current_step ?? 1);

        if (empty($speech)) {
            // No speech captured — repeat question
            $resp = new VoiceResponse();
            $resp->say("I didn't catch that. Let's try again.", ['voice'=>'Polly.Joanna']);
            $resp->redirect(route('twilio.question', ['sid' => $sid]));
            return response($resp,200)->header('Content-Type','text/xml');
        }

        // Save raw transcript for debugging
        $this->saveRawSpeech($call, $step, $speech);

        // Use OpenAI to extract a clean value for the current step
        $fieldName = $this->stepFieldName($step);
        $extracted = $this->openaiExtract($speech, $fieldName);

        if ($extracted === null || $extracted === 'unknown') {
            // Ask to repeat for this step if extraction failed
            $resp = new VoiceResponse();
            $resp->say("Sorry, I couldn't understand that clearly. Let's try that again.", ['voice'=>'Polly.Joanna']);
            $resp->redirect(route('twilio.question', ['sid' => $sid]));
            return response($resp,200)->header('Content-Type','text/xml');
        }

        // Persist the extracted value to DB
        $updates = [];
        if ($fieldName === 'mobile') {
            $updates['mobile'] = preg_replace('/\D/', '', $extracted);
        } elseif ($fieldName === 'email') {
            $updates['email'] = $extracted;
        } else {
            $updates[$fieldName] = $extracted;
        }
        $call->update($updates);

        // Advance step
        if ($step < 3) {
            $call->current_step = $step + 1;
            $call->save();

            $resp = new VoiceResponse();
            $resp->say("Thanks.", ['voice'=>'Polly.Joanna']);
            $resp->pause(['length' => 0.5]);
            $resp->redirect(route('twilio.question', ['sid' => $sid]));
            return response($resp,200)->header('Content-Type','text/xml');
        }

        // Step 3 completed → finish
        $call->status = 'complete';
        $call->save();

        $resp = new VoiceResponse();
        // Summarize captured fields (read digits for mobile)
        $name = $call->name ?? 'not provided';
        $mobile = $call->mobile ? implode(' ', str_split($call->mobile)) : 'not provided';
        $email = $call->email ?? 'not provided';

        // Use SSML for mobile digits
        $resp->say("Thank you. I have recorded the following details.", ['voice'=>'Polly.Joanna']);
        $resp->say("Name: $name.", ['voice'=>'Polly.Joanna']);
        $resp->say("<speak>Phone: <say-as interpret-as=\"digits\">{$call->mobile}</say-as>.</speak>", ['voice'=>'Polly.Joanna']);
        $resp->say("Email: $email.", ['voice'=>'Polly.Joanna']);
        $resp->say("That's all—thanks for your time. Goodbye.", ['voice'=>'Polly.Joanna']);
        $resp->hangup();

        return response($resp,200)->header('Content-Type','text/xml');
    }

    // Outbound helper (keep available if you need it)
    public function outbound($phone)
    {
        try {
            $twilio = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
            $call = $twilio->calls->create(
                $phone,
                env('TWILIO_NUMBER'),
                ['url' => route('twilio.incoming')]
            );
            SurveyCall::create(['phone' => $phone, 'call_sid' => $call->sid]);
            return response()->json(['status'=>'ok','sid'=>$call->sid]);
        } catch (\Throwable $e) {
            \Log::error('OUTBOUND ERROR: '.$e->getMessage());
            return response()->json(['status'=>'error','error'=>$e->getMessage()],500);
        }
    }

    /* ---------------- helpers ---------------- */

    // Map step number to DB field name
    private function stepFieldName(int $step): string
    {
        return match($step) {
            1 => 'name',
            2 => 'mobile',
            3 => 'email',
            default => 'name',
        };
    }

    // Save raw speech to qN_speech column if available
    private function saveRawSpeech(SurveyCall $call, int $step, string $speech)
    {
        $col = "q{$step}_speech";
        try {
            if (\Schema::hasColumn($call->getTable(), $col)) {
                $call->{$col} = $speech;
                $call->save();
            }
        } catch (\Throwable $e) {
            // ignore schema issues
        }
    }

    // Ask OpenAI to extract the single field (returns string or 'unknown' or null)
    private function openaiExtract(string $speech, string $field)
    {
        // Create a focused prompt per field
        $prompts = [
            'name' => "Extract the person's full name from this speech. If not found return \"unknown\". Output only the name.",
            'mobile' => "Extract only the phone number digits from this speech. Remove any spaces or punctuation. If no digits, return \"unknown\".",
            'email' => "Extract the email address from this speech. Convert spoken 'at' and 'dot' to '@' and '.' and output only the email address. If no email, return \"unknown\".",
        ];

        $system = "You are a tool that extracts a single value from the user's spoken text according to the instruction. Return only the value and nothing else.";

        $instruction = $prompts[$field] ?? $prompts['name'];

        try {
            $res = OpenAIClient::chat()->create([
                'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'temperature' => 0.0,
                'max_tokens' => 40,
                'messages' => [
                    ['role'=>'system','content'=>$system],
                    ['role'=>'user','content'=> $instruction . "\n\nSpeech: " . $speech],
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('OpenAI extract error: '.$e->getMessage());
            return null;
        }

        $out = trim($res->choices[0]->message->content ?? '');
        // sanitize outputs
        if ($field === 'mobile') {
            $digits = preg_replace('/\D/','',$out);
            return $digits === '' ? 'unknown' : $digits;
        }
        if ($field === 'email') {
            $candidate = strtolower(trim($out));
            // convert common spoken tokens if model didn't
            $candidate = str_replace([' at ',' AT '],'@',$candidate);
            $candidate = str_replace([' dot ',' DOT '],'.',$candidate);
            $candidate = preg_replace('/\s+/', '', $candidate);
            return filter_var($candidate, FILTER_VALIDATE_EMAIL) ? $candidate : 'unknown';
        }
        // name: keep as-is but remove extraneous quotes
        $name = trim($out, "\"' \t\n\r");
        return $name === '' ? 'unknown' : $name;
    }
}
