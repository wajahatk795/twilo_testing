<?php

use Illuminate\Support\Facades\Route;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Facades\Log;


Route::get('/', function () {
    return view('welcome');
});

Route::post('/twilio/incoming-call', function () {
    Log::info('Incoming call', request()->all());

    $resp = new VoiceResponse();
    $resp->say('Hi, we will ask you three quick questions.', ['voice' => 'Polly.Joanna']);
    // Pipe audio into our Reverb WebSocket
    $resp->connect()->stream(['url' => 'wss://' . config('app.url') . '/media']);
    return response($resp)->header('Content-Type', 'text/xml');
});


