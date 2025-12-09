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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('call_sid')->nullable()->index();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->text('transcript')->nullable();
            $table->json('ai_history')->nullable();
            $table->string('status')->default('queued'); // queued, in-progress, completed
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
