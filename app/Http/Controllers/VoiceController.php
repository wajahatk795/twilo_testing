<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use App\Models\SurveyCall;
use OpenAI\Laravel\Facades\OpenAI as OpenAIClient;
use Illuminate\Support\Str;

class VoiceController extends Controller
{
    /**
     * Incoming call: create record and redirect to conversation entry
     */
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
                'conversation' => json_encode([['role'=>'system','content'=>$this->systemInstruction()]]),
                'state' => 'start'
            ]
        );

        $resp = new VoiceResponse();
        $resp->say("Hello — thanks for taking this call. I have a few quick questions.", ['voice' => 'Polly.Joanna']);
        $resp->pause(['length' => 1]);
        $resp->redirect(route('twilio.converse', ['sid' => $sid]));
        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Converse: ask the model what to say next (returns small JSON speak + done)
     */
    public function converse(Request $request)
    {
        $sid = $request->input('sid');
        $call = SurveyCall::where('call_sid', $sid)->first();

        if (!$call) {
            $resp = new VoiceResponse();
            $resp->say("Call record not found. Goodbye.");
            $resp->hangup();
            return response($resp, 200)->header('Content-Type', 'text/xml');
        }

        $history = json_decode($call->conversation ?: '[]', true);
        $historyForApi = $history;
        $historyForApi[] = ['role' => 'user', 'content' => "Decide the next thing to say to the caller. Reply EXACTLY with JSON: {\"speak\":\"<text>\",\"done\":true|false,\"store\":{}}. Keep 'speak' short (<= 40 words). Do not output anything outside JSON."];

        try {
            $res = OpenAIClient::chat()->create([
                'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'temperature' => (float) env('OPENAI_TEMP', 0.2),
                'max_tokens' => 120,
                'messages' => $historyForApi,
            ]);
        } catch (\Throwable $e) {
            \Log::error('OpenAI converse error: '.$e->getMessage());
            $resp = new VoiceResponse();
            $resp->say("Sorry, a system error occurred. Goodbye.");
            $resp->hangup();
            return response($resp, 200)->header('Content-Type', 'text/xml');
        }

        $assistantText = trim($res->choices[0]->message->content ?? '');
        // append assistant raw
        $history[] = ['role'=>'assistant','content'=>$assistantText];
        $call->conversation = json_encode($history);
        $call->save();

        $json = $this->safeJsonDecode($assistantText);

        // fallback default if model didn't return proper JSON
        if (!$json || !isset($json['speak'])) {
            $speak = "Could you please tell me your full name?";
            $done = false;
            $store = [];
        } else {
            $speak = $json['speak'];
            $done  = !empty($json['done']);
            $store = $json['store'] ?? [];
        }

        // If model wants to store something, persist minimal fields now (non-authoritative)
        if (!empty($store) && is_array($store)) {
            $this->persistStoreFields($call, $store);
        }

        // Build TwiML - put prompt inside gather so Twilio listens while speaking
        $resp = new VoiceResponse();
        $g = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 8,
            'action' => route('twilio.handleSpeech', ['sid' => $sid]),
            'method' => 'POST',
        ]);
        // if speak contains digits we may want as digits — the model should produce text suitable for TTS
        $g->say($speak, ['voice' => 'Polly.Joanna']);

        if ($done) {
            $resp->say("Alright, thank you. Goodbye.", ['voice' => 'Polly.Joanna']);
            $resp->hangup();
            $call->status = 'complete';
            $call->save();
        } else {
            $resp->say("If you don't respond I'll repeat.", ['voice' => 'Polly.Joanna']);
            $resp->redirect(route('twilio.converse', ['sid' => $sid]));
        }

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Handle speech result from Twilio, append to conversation and loop
     */
    public function handleSpeech(Request $request)
    {
        $sid = $request->input('sid');
        $speech = trim((string)$request->input('SpeechResult', ''));

        \Log::info('TWILIO HANDLE SPEECH', compact('sid','speech'));

        $call = SurveyCall::where('call_sid', $sid)->first();
        if (!$call) {
            $resp = new VoiceResponse();
            $resp->say("Call record missing. Goodbye.");
            $resp->hangup();
            return response($resp, 200)->header('Content-Type', 'text/xml');
        }

        // Append user speech to conversation
        $history = json_decode($call->conversation ?: '[]', true);
        $history[] = ['role' => 'user', 'content' => $speech];
        $call->conversation = json_encode($history);
        // Optionally: store last speech per-step (columns q1_speech etc.) – we'll write a tiny heuristic
        $this->storeLatestSpeechColumn($call, $speech);

        $call->save();

        // Now ask model to decide next step given user's reply
        $userPrompt = "User replied: " . $speech . ". Decide the next thing to say. Reply EXACTLY with JSON: {\"speak\":\"...\",\"done\":true|false,\"store\":{}}. 'store' may contain key:value pairs to persist (like name/email/phone). Keep 'speak' concise (<=40 words).";

        $historyForApi = json_decode($call->conversation ?: '[]', true);
        $historyForApi[] = ['role'=>'user','content'=>$userPrompt];

        try {
            $res = OpenAIClient::chat()->create([
                'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'temperature' => (float) env('OPENAI_TEMP', 0.25),
                'max_tokens' => 140,
                'messages' => $historyForApi,
            ]);
        } catch (\Throwable $e) {
            \Log::error('OpenAI handleSpeech error: '.$e->getMessage());
            $resp = new VoiceResponse();
            $resp->say("Sorry, something went wrong. Goodbye.");
            $resp->hangup();
            return response($resp, 200)->header('Content-Type', 'text/xml');
        }

        $assistantText = trim($res->choices[0]->message->content ?? '');
        $history[] = ['role'=>'assistant','content'=>$assistantText];
        $call->conversation = json_encode($history);
        $call->save();

        $json = $this->safeJsonDecode($assistantText);

        if (!$json || !isset($json['speak'])) {
            $speak = "Sorry, could you repeat that please?";
            $done = false;
            $store = [];
        } else {
            $speak = $json['speak'];
            $done  = !empty($json['done']);
            $store = $json['store'] ?? [];
        }

        // Persist any store fields returned by the model
        if (!empty($store) && is_array($store)) {
            $this->persistStoreFields($call, $store);
        }

        // If done, say and hang up
        $resp = new VoiceResponse();
        if ($done) {
            $resp->say($speak, ['voice' => 'Polly.Joanna']);
            $resp->say("Thank you. Goodbye.", ['voice' => 'Polly.Joanna']);
            $resp->hangup();
            $call->status = 'complete';
            $call->save();
            return response($resp, 200)->header('Content-Type', 'text/xml');
        }

        // Otherwise gather next user input
        $g = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 8,
            'action' => route('twilio.handleSpeech', ['sid' => $sid]),
            'method' => 'POST',
        ]);
        $g->say($speak, ['voice' => 'Polly.Joanna']);

        // fallback if no speech
        $resp->say("I didn't hear anything. I'll ask again.", ['voice' => 'Polly.Joanna']);
        $resp->redirect(route('twilio.converse', ['sid' => $sid]));

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Outbound trigger (unchanged)
     */
    public function outbound($phone)
    {
        $twilio = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        $call = $twilio->calls->create(
            $phone,
            env('TWILIO_NUMBER'),
            ['url' => route('twilio.incoming')]
        );
        SurveyCall::create(['phone'=>$phone, 'call_sid'=>$call->sid]);
        return back()->with('status', 'Call placed');
    }

    /**
     * Persist store fields returned by model to DB safely (only specific keys)
     */
    private function persistStoreFields(SurveyCall $call, array $store)
    {
        $allowed = ['name','email','mobile','dob'];
        $update = [];
        foreach ($store as $k=>$v) {
            $k = strtolower($k);
            if (in_array($k, $allowed)) {
                // sanitize basic
                $val = trim((string)$v);
                if ($k === 'mobile') $val = preg_replace('/\D/','',$val);
                if ($k === 'email') $val = filter_var($val, FILTER_SANITIZE_EMAIL);
                $update[$k] = $val;
            }
        }
        if (!empty($update)) {
            $call->update($update);
        }
    }

    /**
     * store latest speech raw into qN_speech column if present
     */
    private function storeLatestSpeechColumn(SurveyCall $call, string $speech)
    {
        // heuristics: append to next empty qX_speech column (q1..q5) if available
        for ($i = 1; $i <= 5; $i++) {
            $col = "q{$i}_speech";
            if ($this->SchemaHasColumn($call->getTable(), $col)) {
                $current = $call->{$col} ?? null;
                if (empty($current)) {
                    $call->{$col} = $speech;
                    break;
                }
            }
        }
    }

    /**
     * minimal system instruction for model
     */
    private function systemInstruction(): string
    {
        return implode("\n", [
            "You are Joanna, a short, polite telephone agent.",
            "When asked, reply ONLY with JSON like {\"speak\":\"...\",\"done\":true|false,\"store\":{}}.",
            "'speak' should be under 40 words. 'store' may include fields to save (name,email,mobile,dob).",
        ]);
    }

    /**
     * Try to extract the first JSON object from the assistant text
     */
    private function safeJsonDecode(string $text)
    {
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) return null;
        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function SchemaHasColumn($table, $column) {
        try {
            return \Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
