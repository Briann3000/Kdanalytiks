<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_ai_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->text('fact');
            $table->integer('importance')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'survey_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_ai_memories');
    }
};
