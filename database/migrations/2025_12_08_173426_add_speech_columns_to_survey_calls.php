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
        Schema::table('survey_calls', function (Blueprint $table) {
            $table->after('status', function (Blueprint $table) {
                $table->text('q1_speech')->nullable();
                $table->text('q2_speech')->nullable();
                $table->text('q3_speech')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_calls', function (Blueprint $table) {
            $table->dropColumn(['q1_speech', 'q2_speech', 'q3_speech']);
        });
    }
};
