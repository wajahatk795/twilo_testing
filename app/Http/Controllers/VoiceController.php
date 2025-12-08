<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use App\Models\SurveyCall;
use OpenAI\Laravel\Facades\OpenAI as OpenAIClient;

class VoiceController extends Controller
{
    // Incoming call: create record (if new) and jump to conversation
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
            ]
        );

        $resp = new VoiceResponse();
        $resp->say("Hi — thanks for answering. I have a few quick questions.", ['voice' => 'Polly.Joanna']);
        $resp->pause(['length' => 1]);
        $resp->redirect(route('twilio.converse', ['sid' => $sid]));

        return response($resp, 200)->header('Content-Type', 'text/xml');
    }

    // Converse: ask model what to say next (model returns small JSON)
    public function converse(Request $request)
    {
        $sid = $request->input('sid');
        $call = SurveyCall::where('call_sid', $sid)->first();

        if (!$call) {
            $resp = new VoiceResponse();
            $resp->say('Call record not found. Goodbye.', ['voice' => 'Polly.Joanna']);
            $resp->hangup();
            return response($resp,200)->header('Content-Type','text/xml');
        }

        $history = json_decode($call->conversation ?: '[]', true);
        $historyForApi = $history;
        $historyForApi[] = ['role'=>'user','content'=>
            "Decide the next short phrase to say to the caller. REPLY ONLY WITH JSON: " .
            '{"speak":"...","done":true|false,"store":{}}. ' .
            "Keep 'speak' under 40 words. 'store' may include {name,email,mobile,dob} to persist."
        ];

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
            $resp->say("Sorry, a system error occurred. Goodbye.", ['voice' => 'Polly.Joanna']);
            $resp->hangup();
            return response($resp,200)->header('Content-Type','text/xml');
        }

        $assistantText = trim($res->choices[0]->message->content ?? '');
        $history[] = ['role'=>'assistant','content'=>$assistantText];
        $call->conversation = json_encode($history);
        $call->save();

        $json = $this->safeJsonDecode($assistantText);

        if (!$json || !isset($json['speak'])) {
            // fallback
            $speak = "Could you please tell me your full name?";
            $done = false;
            $store = [];
        } else {
            $speak = $json['speak'];
            $done = !empty($json['done']);
            $store = $json['store'] ?? [];
        }

        if (!empty($store)) $this->persistStoreFields($call, $store);

        $resp = new VoiceResponse();
        $g = $resp->gather([
            'input' => 'speech',
            'speechTimeout' => 'auto',
            'timeout' => 8,
            'action' => route('twilio.handleSpeech', ['sid' => $sid]),
            'method' => 'POST',
        ]);
        $g->say($speak, ['voice' => 'Polly.Joanna']);

        if ($done) {
            $resp->say("Okay, thank you. Goodbye.", ['voice' => 'Polly.Joanna']);
            $resp->hangup();
            $call->status = 'complete';
            $call->save();
        } else {
            // fallback if gather fails
            $resp->say("If I don't hear anything, I'll repeat.", ['voice'=>'Polly.Joanna']);
            $resp->redirect(route('twilio.converse', ['sid' => $sid]));
        }

        return response($resp,200)->header('Content-Type','text/xml');
    }

    // Handle speech from Twilio, append to conversation and continue
    public function handleSpeech(Request $request)
    {
        $sid = $request->input('sid');
        $speech = trim((string)$request->input('SpeechResult',''));

        \Log::info('TWILIO HANDLE SPEECH', compact('sid','speech'));

        $call = SurveyCall::where('call_sid', $sid)->first();
        if (!$call) {
            $resp = new VoiceResponse();
            $resp->say('Call record missing. Goodbye.', ['voice'=>'Polly.Joanna']);
            $resp->hangup();
            return response($resp,200)->header('Content-Type','text/xml');
        }

        $history = json_decode($call->conversation ?: '[]', true);
        $history[] = ['role'=>'user','content'=>$speech];
        $call->conversation = json_encode($history);
        $this->storeLatestSpeechColumn($call, $speech); // non-fatal helper
        $call->save();

        $userPrompt = "User replied: " . $speech .
            ". Decide the next short phrase to say. REPLY ONLY WITH JSON: " .
            '{"speak":"...","done":true|false,"store":{}}. ' .
            "Keep 'speak' concise (<=40 words).";

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
            $resp->say("Sorry, something went wrong. Goodbye.", ['voice'=>'Polly.Joanna']);
            $resp->hangup();
            return response($resp,200)->header('Content-Type','text/xml');
        }

        $assistantText = trim($res->choices[0]->message->content ?? '');
        $history[] = ['role'=>'assistant','content'=>$assistantText];
        $call->conversation = json_encode($history);
        $call->save();

        $json = $this->safeJsonDecode($assistantText);

        if (!$json || !isset($json['speak'])) {
            $speak = "Sorry, could you repeat that more clearly?";
            $done = false;
            $store = [];
        } else {
            $speak = $json['speak'];
            $done = !empty($json['done']);
            $store = $json['store'] ?? [];
        }

        if (!empty($store)) $this->persistStoreFields($call, $store);

        $resp = new VoiceResponse();
        if ($done) {
            $resp->say($speak, ['voice'=>'Polly.Joanna']);
            $resp->say("Thank you. Goodbye.", ['voice'=>'Polly.Joanna']);
            $resp->hangup();
            $call->status = 'complete';
            $call->save();
            return response($resp,200)->header('Content-Type','text/xml');
        }

        $g = $resp->gather([
            'input'=>'speech',
            'speechTimeout'=>'auto',
            'timeout'=>8,
            'action'=>route('twilio.handleSpeech', ['sid'=>$sid]),
            'method'=>'POST',
        ]);
        $g->say($speak, ['voice'=>'Polly.Joanna']);

        $resp->say("If I don't hear anything I'll repeat.", ['voice'=>'Polly.Joanna']);
        $resp->redirect(route('twilio.converse', ['sid'=>$sid]));

        return response($resp,200)->header('Content-Type','text/xml');
    }

    // Outbound helper (unchanged)
    public function outbound($phone)
    {
        $twilio = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        $call = $twilio->calls->create(
            $phone,
            env('TWILIO_NUMBER'),
            ['url' => route('twilio.incoming')]
        );
        SurveyCall::create(['phone'=>$phone,'call_sid'=>$call->sid]);
        return back()->with('status','Call placed');
    }

    // Persist store fields safely
    private function persistStoreFields(SurveyCall $call, array $store)
    {
        $allowed = ['name','email','mobile','dob'];
        $update = [];
        foreach ($store as $k => $v) {
            $k = strtolower($k);
            if (in_array($k, $allowed)) {
                $val = trim((string)$v);
                if ($k === 'mobile') $val = preg_replace('/\D/','',$val);
                if ($k === 'email') $val = filter_var($val, FILTER_SANITIZE_EMAIL);
                $update[$k] = $val;
            }
        }
        if (!empty($update)) $call->update($update);
    }

    // store latest speech into qN_speech heuristically (non-fatal)
    private function storeLatestSpeechColumn(SurveyCall $call, string $speech)
    {
        for ($i=1;$i<=5;$i++) {
            $col = "q{$i}_speech";
            if (method_exists($call,'getTable') && \Schema::hasColumn($call->getTable(), $col)) {
                if (empty($call->{$col})) {
                    $call->{$col} = $speech;
                    break;
                }
            }
        }
    }

    // system instruction for model
    private function systemInstruction(): string
    {
        return implode("\n", [
            "You are Joanna, a polite telephone agent whose job is to capture lead details (name,email,mobile,dob).",
            "Respond ONLY with a JSON object and nothing else, in the exact form: {\"speak\":\"...\",\"done\":true|false,\"store\":{}}.",
            "'speak' should be <= 40 words, natural, concise. 'store' may include fields to persist (name,email,mobile,dob).",
            "Do not include any extra commentary or explanation outside the JSON.",
        ]);
    }

    // extract first JSON block from assistant text
    private function safeJsonDecode(string $text)
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) return null;
        $jsonText = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($jsonText, true);
        return is_array($decoded) ? $decoded : null;
    }
}
