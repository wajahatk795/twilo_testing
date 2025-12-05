<?php
namespace App\Http\Controllers;

use Twilio\TwiML\VoiceResponse;
use App\Models\SurveyCall;
use OpenAI\Laravel\Facades\OpenAI;

class VoiceController extends Controller
{
    public function incoming()
    {
         \Log::info('=== TWILIO INCOMING CALL ===', request()->all());   // <- TEST-PRINT
        $resp = new VoiceResponse();
        $sid  = request('CallSid');
        SurveyCall::firstOrCreate(['call_sid' => $sid], ['phone' => request('From')]);

        $resp->say('Hi, we will ask you three quick questions.', ['voice' => 'Polly.Joanna']);
        $resp->redirect(route('twilio.question', ['q' => 1, 'sid' => $sid]));
        return response($resp)->header('Content-Type', 'text/xml');
    }

    public function question()
    {
        $q   = (int) request('q');   // 1=name 2=email 3=dob
        $sid = request('sid');
        $resp = new VoiceResponse();

        $prompts = [
            1 => 'Please say your full name after the beep.',
            2 => 'Please spell your email address.',
            3 => 'Please say your date of birth, for example March 14 1988.',
        ];

        $resp->say($prompts[$q], ['voice' => 'Polly.Joanna']);
        $resp->gather([
            'input'         => 'speech',
            'speechTimeout' => 'auto',
            'action'        => route('twilio.handle', ['q' => $q, 'sid' => $sid]),
            'method'        => 'POST',
        ]);

        return response($resp)->header('Content-Type', 'text/xml');
    }

    public function handle()
    {
        $q      = (int) request('q');
        $sid    = request('sid');
        $speech = request('SpeechResult');

        $res = OpenAI::chat()->create([
            'model'       => 'gpt-3.5-turbo',
            'temperature' => 0,
            'messages'    => [
                ['role' => 'system', 'content' => 'Extract only the requested value. If impossible return "unknown".'],
                ['role' => 'user',   'content' => "Question $q speech: $speech"]
            ],
        ]);
        $answer = trim($res->choices[0]->message->content);

        $call = SurveyCall::where('call_sid', $sid)->first();
        match ($q) {
            1 => $call->update(['name' => $answer]),
            2 => $call->update(['email' => $answer]),
            3 => $call->update(['dob' => $answer, 'status' => 'complete']),
        };

        if ($q < 3) {
            return redirect()->to(route('twilio.question', ['q' => $q + 1, 'sid' => $sid]));
        }

        $resp = new VoiceResponse();
        $resp->say('Thank you, we have everything we need. Goodbye.');
        return response($resp)->header('Content-Type', 'text/xml');
    }

    public function outbound($phone)
    {
        $twilio = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        $call   = $twilio->calls->create(
            $phone,
            env('TWILIO_NUMBER'),
            ['url' => route('twilio.incoming')]
        );
        SurveyCall::create(['phone' => $phone, 'call_sid' => $call->sid]);
        return back()->with('status', 'Call placed');
    }
}