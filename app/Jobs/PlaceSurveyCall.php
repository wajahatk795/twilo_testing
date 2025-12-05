<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PlaceSurveyCall implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $twilio = new \Twilio\Rest\Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $twilio->calls->create(
            $this->phone,                                 // to
            config('services.twilio.number'),             // from
            ['url' => route('twilio.incoming-call')]      // same TwiML
        );
        SurveyCall::create(['phone' => $this->phone, 'call_sid' => 'pending']);
    }
}
