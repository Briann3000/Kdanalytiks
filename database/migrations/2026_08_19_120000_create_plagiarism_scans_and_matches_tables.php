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
        Schema::create('plagiarism_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('title');
            $table->string('original_filename')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->default('text'); // text, docx, pdf, txt
            $table->longText('content');
            $table->integer('word_count')->default(0);
            $table->integer('character_count')->default(0);
            $table->decimal('similarity_percentage', 5, 2)->default(0.00);
            $table->decimal('ai_percentage', 5, 2)->default(0.00);
            $table->boolean('exclude_quotes')->default(true);
            $table->boolean('exclude_references')->default(true);
            $table->boolean('exclude_small_matches')->default(true);
            $table->integer('min_words_threshold')->default(8);
            $table->string('status')->default('completed'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->json('summary_metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('plagiarism_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained('plagiarism_scans')->cascadeOnDelete();
            $table->text('source_url')->nullable();
            $table->string('source_title')->nullable();
            $table->string('source_domain')->nullable();
            $table->text('matched_text');
            $table->text('original_snippet');
            $table->decimal('similarity_score', 5, 2)->default(100.00);
            $table->integer('start_offset')->default(0);
            $table->integer('end_offset')->default(0);
            $table->string('match_type')->default('web'); // web, academic, internal, quote, citation
            $table->boolean('is_excluded')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plagiarism_matches');
        Schema::dropIfExists('plagiarism_scans');
    }
};
