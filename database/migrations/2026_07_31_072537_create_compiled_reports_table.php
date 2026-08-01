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
        Schema::create('compiled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('survey_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->string('original_chapters_path')->nullable();
            $table->longText('proofread_chapters')->nullable(); // Stores original vs proofread segments/diff JSON
            $table->longText('chapter4_content')->nullable();
            $table->longText('chapter5_content')->nullable();
            $table->string('final_docx_path')->nullable();
            $table->string('status')->default('processing');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compiled_reports');
    }
};
