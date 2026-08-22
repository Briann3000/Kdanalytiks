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
        Schema::table('plagiarism_scans', function (Blueprint $table) {
            if (!Schema::hasColumn('plagiarism_scans', 'exclude_citations')) {
                $table->boolean('exclude_citations')->default(true)->after('exclude_references');
            }
            if (!Schema::hasColumn('plagiarism_scans', 'excluded_domains')) {
                $table->json('excluded_domains')->nullable()->after('min_words_threshold');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plagiarism_scans', function (Blueprint $table) {
            $table->dropColumn(['exclude_citations', 'excluded_domains']);
        });
    }
};
