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
        Schema::create('proofreads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->default('Proofread Document');
            $table->json('paragraphs')->nullable();
            $table->timestamps();
        });

        Schema::table('research_proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('research_proposals', 'budget')) {
                $table->json('budget')->nullable();
            }
            if (!Schema::hasColumn('research_proposals', 'custom_instructions')) {
                $table->text('custom_instructions')->nullable();
            }
        });

        Schema::table('compiled_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('compiled_reports', 'custom_instructions')) {
                $table->text('custom_instructions')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proofreads');

        Schema::table('research_proposals', function (Blueprint $table) {
            $table->dropColumn(['budget', 'custom_instructions']);
        });

        Schema::table('compiled_reports', function (Blueprint $table) {
            $table->dropColumn(['custom_instructions']);
        });
    }
};
