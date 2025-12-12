<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use App\Models\SurveyCall;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use OpenAI\Laravel\Facades\OpenAI as OpenAIClient;

class VoiceController extends Controller
{
    public function twilio()
    {
        return view('twilio.index');
    }

    // Incoming call: create record (or find) and start at step 1
    public function incoming(Request $request)
    {
        \Log::info('TWILIO INCOMING', $request->all());

        $sid    = $request->input('CallSid');
        $from   = $request->input('From');
        $to     = $request->input('To');  // <---- TWILIO number called

        /** ---------------------------------------------------------
         * 🔥 STEP 1 — Detect Tenant from incoming TWILIO number
         * ---------------------------------------------------------
         */
        $phoneNumber = PhoneNumber::where('number', $from)->first();

        if (!$phoneNumber) {
            return $this->twilioError("Unauthorized phone number.");
        }

        $tenantId = $phoneNumber->user->tenant_id ?? null;
        $ivrFlow  = $phoneNumber->ivr_flow ?? []; // Custom questions per tenant

        /** ---------------------------------------------------------
         * 🔥 STEP 2 — Create (or find) SurveyCall tied to tenant
         * ---------------------------------------------------------
         */
        $call = SurveyCall::firstOrCreate(
            ['call_sid' => $sid],
            [
                'tenant_id'  => $tenantId,
                'phone'      => $from,
                'status'     => 'started',
                'current_step' => 1,
                'conversation' => json_encode([
                    ['role'=>'system', 'content'=>'lead-capture session']
                ]),
                'ivr_flow'   => json_encode($ivrFlow),
            ]
        );

        /** ---------------------------------------------------------
         * 🔥 STEP 3 — Start call
         * ---------------------------------------------------------
         */
        $resp = new VoiceResponse();
        $resp->say("Hi — thanks for taking this call.", ['voice' => 'Polly.Joanna']);
        $resp->pause(['length' => 1]);

        $resp->redirect(route('twilio.question', ['sid' => $sid]));
        return $this->xml($resp);
    }

    // Show the current question for this call (one-by-one)
    public function question(Request $request)
    {
        $sid  = $request->input('sid');
        $call = SurveyCall::where('call_sid', $sid)->first();

        if (!$call) {
            return $this->twilioError("Call not found.");
        }

        /** Load dynamic questions from ivr_flow */
        $ivr = json_decode($call->ivr_flow, true) ?? [];

        // Default fallback questions if none set
        $default = [
            1 => "Please tell me your full name.",
            2 => "Please say your phone number, one digit at a time.",
            3 => "Please spell your email address."
        ];

        $prompts = $ivr['questions'] ?? $default;

        $step = (int) $call->current_step;
        $prompt = $prompts[$step] ?? "Thank you.";

        $resp = new VoiceResponse();

        $g = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 7,
            'action' => route('twilio.handle', ['sid' => $sid]),
            'method' => 'POST',
        ]);

        $g->say($prompt, ['voice' => 'Polly.Joanna']);

        $resp->say("Let's try again.", ['voice'=>'Polly.Joanna']);
        $resp->redirect(route('twilio.question', ['sid'=>$sid]));

        return $this->xml($resp);
    }

    // Handle captured speech, use OpenAI to extract/normalize the single field for the current step
    public function handle(Request $request)
    {
        $sid    = $request->input('sid');
        $speech = trim($request->input('SpeechResult', ''));

        $call = SurveyCall::where('call_sid', $sid)->first();
        if (!$call) {
            return $this->twilioError("Call not found.");
        }

        /** Load dynamic IVR flow */
        $ivr = json_decode($call->ivr_flow, true) ?? [];
        $defaultFields = [
            1 => 'name',
            2 => 'mobile',
            3 => 'email',
        ];
        $fields = $ivr['fields'] ?? $defaultFields;

        $step = $call->current_step;
        $fieldName = $fields[$step] ?? null;

        if (!$fieldName) {
            return $this->twilioError("Invalid step.");
        }

        if (!$speech) {
            return $this->repeatStep($sid, "I didn't catch that.");
        }

        $this->saveRawSpeech($call, $step, $speech);

        /** 🔥 extract clean field using AI */
        $value = $this->openaiExtract($speech, $fieldName);

        if (!$value || $value === 'unknown') {
            return $this->repeatStep($sid, "Sorry, I couldn't understand that.");
        }

        /** Save field */
        if ($fieldName === 'mobile') {
            $value = preg_replace('/\D/', '', $value);
        }

        $call->update([$fieldName => $value]);

        /** Move to next step */
        if (isset($fields[$step + 1])) {
            $call->update(['current_step' => $step + 1]);

            $resp = new VoiceResponse();
            $resp->say("Thank you.", ['voice' => 'Polly.Joanna']);
            $resp->redirect(route('twilio.question', ['sid' => $sid]));

            return $this->xml($resp);
        }

        /** Finish call */
        $call->update(['status' => 'complete']);

        $resp = new VoiceResponse();
        $resp->say("Thanks. Your responses were recorded.", ['voice'=>'Polly.Joanna']);
        $resp->hangup();

        return $this->xml($resp);
    }

    // Outbound helper (keep available if you need it)
    public function outbound(Request $request)
    {
        $phone = $request->input('phone'); // <-- User manually types phone

        $request->validate([
            'phone'  => 'required',
        ]);

        try {
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

            return redirect()->back()->with('success', 'Call started successfully! SID: '.$call->sid);

        } catch (\Throwable $e) {
            \Log::error('OUTBOUND ERROR: '.$e->getMessage());
            return redirect()->back()->with('error', 'Failed to start call: '.$e->getMessage());
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

    private function xml($resp)
    {
        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    private function twilioError($msg)
    {
        $resp = new VoiceResponse();
        $resp->say($msg, ['voice'=>'Polly.Joanna']);
        $resp->hangup();
        return $this->xml($resp);
    }

    private function repeatStep($sid, $msg)
    {
        $resp = new VoiceResponse();
        $resp->say($msg, ['voice'=>'Polly.Joanna']);
        $resp->redirect(route('twilio.question', ['sid' => $sid]));
        return $this->xml($resp);
    }
}
