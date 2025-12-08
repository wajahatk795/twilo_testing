<?php

use Illuminate\Support\Facades\Route;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\VoiceController;


Route::get('/', function () {
    return view('welcome');
});

// Route::post('/twilio/incoming', [VoiceController::class,'incoming'])->name('twilio.incoming');
// Route::post('/twilio/question', [VoiceController::class,'question'])->name('twilio.question');
// Route::post('/twilio/handle',   [VoiceController::class,'handle'])  ->name('twilio.handle');
// Route::get('/call/{phone}',     [VoiceController::class,'outbound'])->name('twilio.outbound');
// Route::post('/twilio/confirm', [VoiceController::class, 'confirm'])->name('twilio.confirm');

Route::post('/twilio/incoming', [VoiceController::class,'incoming'])->name('twilio.incoming');
Route::post('/twilio/converse', [VoiceController::class,'converse'])->name('twilio.converse');
Route::post('/twilio/handleSpeech', [VoiceController::class,'handleSpeech'])->name('twilio.handleSpeech');
Route::get('/twilio/outbound/{phone}', [VoiceController::class,'outbound'])->name('twilio.outbound'); // optional


// Route::get('/openai-test', [\App\Http\Controllers\VoiceController::class, 'openaiTest']);


