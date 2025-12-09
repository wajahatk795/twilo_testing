<?php

use Illuminate\Support\Facades\Route;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\VoiceController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Container\Attributes\Auth;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes

Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
Route::get('/login', [LoginController::class, 'showLogin'])->name('auth.login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('auth.login.post');
Route::get('/register', [RegisterController::class, 'showRegister'])->name('auth.register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->name('auth.register.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout');

// Route::post('/twilio/incoming', [VoiceController::class,'incoming'])->name('twilio.incoming');
// Route::post('/twilio/question', [VoiceController::class,'question'])->name('twilio.question');
// Route::post('/twilio/handle',   [VoiceController::class,'handle'])  ->name('twilio.handle');
// Route::get('/call/{phone}',     [VoiceController::class,'outbound'])->name('twilio.outbound');
// Route::post('/twilio/confirm', [VoiceController::class, 'confirm'])->name('twilio.confirm');

Route::post('/twilio/incoming', [VoiceController::class,'incoming'])->name('twilio.incoming');
Route::post('/twilio/question',  [VoiceController::class,'question'])->name('twilio.question');
Route::post('/twilio/handle',    [VoiceController::class,'handle'])->name('twilio.handle');
Route::post('/twilio/outbound/{phone}', [VoiceController::class,'outbound'])->name('twilio.outbound');


// Route::get('/openai-test', [\App\Http\Controllers\VoiceController::class, 'openaiTest']);

Route::get('/admin', [AdminController::class, 'dashboard'])
    ->middleware(\App\Http\Middleware\RedirectIfNotAuthBack::class)
    ->name('admin.dashboard');
