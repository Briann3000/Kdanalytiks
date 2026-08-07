<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Make survey_id nullable on survey_ai_threads so that standalone
     * Socius chat sessions (not tied to any survey) can be stored.
     */
    public function up(): void
    {
        // 1. Safely drop foreign key if it exists
        try {
            Schema::table('survey_ai_threads', function (Blueprint $table) {
                $table->dropForeign(['survey_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key does not exist or has a different constraint name; ignore
        }

        // 2. Change survey_id column to nullable
        Schema::table('survey_ai_threads', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->nullable()->change();
        });

        // 3. Re-add foreign key constraint safely
        try {
            Schema::table('survey_ai_threads', function (Blueprint $table) {
                $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            });
        } catch (\Throwable $e) {
            // Constraint may already exist; ignore
        }

        // 4. Add context_type column if missing
        if (!Schema::hasColumn('survey_ai_threads', 'context_type')) {
            Schema::table('survey_ai_threads', function (Blueprint $table) {
                $table->string('context_type')->nullable()->after('survey_group_id')
                    ->comment('e.g. transcription, general — for standalone Socius threads');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('survey_ai_threads', 'context_type')) {
            Schema::table('survey_ai_threads', function (Blueprint $table) {
                $table->dropColumn('context_type');
            });
        }

        try {
            Schema::table('survey_ai_threads', function (Blueprint $table) {
                $table->dropForeign(['survey_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('survey_ai_threads', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->nullable(false)->change();
        });
    }
};
