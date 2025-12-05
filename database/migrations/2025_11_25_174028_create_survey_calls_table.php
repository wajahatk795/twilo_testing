<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_calls', function (Blueprint $t) {
            $t->id();
            $t->string('call_sid')->unique();      // Twilio CallSid
            $t->string('phone');                   // customer E.164
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('dob')->nullable();
            $t->enum('status', ['init', 'complete'])->default('init');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_calls');
    }
};
