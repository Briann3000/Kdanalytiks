<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('survey_ai_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 20);
            $table->longText('content')->nullable();
            $table->boolean('include_survey_context')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_ai_messages');
    }
};
