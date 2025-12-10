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

Route::namespace('App\Http\Controllers\Admin')->middleware(\App\Http\Middleware\RedirectIfNotAuthBack::class)->group(function () {

    Route::get('/admin', 'AdminController@dashboard')->name('admin.dashboard');
    Route::get('/admin/company', '\App\Http\Controllers\TenantController@index')->name('company.admin');
    Route::get('/admin/company/{tenant}/edit', '\App\Http\Controllers\TenantController@edit')->name('company.edit.admin');
    Route::put('/admin/company/{tenant}/edit', '\App\Http\Controllers\TenantController@update')->name('company.edit.admin');
    Route::delete('admin/company/{tenant}', '\App\Http\Controllers\TenantController@destroy')->name('company.destroy.admin');
    Route::get('/tenants/data', '\App\Http\Controllers\TenantController@getData')->name('tenants.data');
    Route::get('admin/create', '\App\Http\Controllers\TenantController@create')->name('create.admin');
    Route::post('admin/store', '\App\Http\Controllers\TenantController@store')->name('tenants.store');


});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLogin'])->name('auth.login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('auth.login.post');
Route::get('/register', [RegisterController::class, 'showRegister'])->name('auth.register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->name('auth.register.post')->middleware('guest');
Route::post('/register/company', [RegisterController::class, 'registerCompany'])->name('auth.register.company');
Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout');

// Route::post('/twilio/incoming', [VoiceController::class,'incoming'])->name('twilio.incoming');
// Route::post('/twilio/question', [VoiceController::class,'question'])->name('twilio.question');
// Route::post('/twilio/handle',   [VoiceController::class,'handle'])  ->name('twilio.handle');
// Route::get('/call/{phone}',     [VoiceController::class,'outbound'])->name('twilio.outbound');
// Route::post('/twilio/confirm', [VoiceController::class, 'confirm'])->name('twilio.confirm');

Route::post('/twilio/incoming', [VoiceController::class, 'incoming'])->name('twilio.incoming');
Route::post('/twilio/question',  [VoiceController::class, 'question'])->name('twilio.question');
Route::post('/twilio/handle',    [VoiceController::class, 'handle'])->name('twilio.handle');
Route::post('/twilio/outbound/{phone}', [VoiceController::class, 'outbound'])->name('twilio.outbound');


// Route::get('/openai-test', [\App\Http\Controllers\VoiceController::class, 'openaiTest']);
