<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization_resource_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained('organizations')->onDelete('cascade');

            // Limits (-1 = unlimited)
            $table->integer('ai_analyses_limit')->default(5);
            $table->integer('transcription_minutes_limit')->default(2);
            $table->integer('socius_chat_sessions_limit')->default(20);
            $table->integer('report_exports_limit')->default(5);
            $table->integer('survey_limit')->default(5);

            // Usage counters
            $table->integer('ai_analyses_used')->default(0);
            $table->integer('transcription_minutes_used')->default(0);
            $table->integer('socius_chat_sessions_used')->default(0);
            $table->integer('report_exports_used')->default(0);

            $table->timestamp('reset_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_resource_pools');
    }
};
