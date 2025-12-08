<?php

use Illuminate\Support\Facades\Route;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\VoiceController;


Route::get('/', function () {
    return view('welcome');
});

Route::post('/twilio/incoming', [VoiceController::class,'incoming'])->name('twilio.incoming');
Route::post('/twilio/question', [VoiceController::class,'question'])->name('twilio.question');
Route::post('/twilio/handle',   [VoiceController::class,'handle'])  ->name('twilio.handle');
Route::get('/call/{phone?}',     [VoiceController::class,'outbound'])->name('twilio.outbound');


Route::get('/openai-test', [\App\Http\Controllers\VoiceController::class, 'openaiTest']);


