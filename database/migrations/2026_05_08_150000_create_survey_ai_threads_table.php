<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_ai_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->default('New chat');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();

            $table->index(['survey_id', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_ai_threads');
    }
};
