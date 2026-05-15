<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_ai_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('survey_ai_threads')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('survey_ai_messages')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('storage_path');
            $table->longText('extracted_text')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['thread_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_ai_attachments');
    }
};
