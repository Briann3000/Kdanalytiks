<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('survey_inferential_analyses', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_inferential_analyses', 'is_included_in_report')) {
                $table->boolean('is_included_in_report')->default(false)->after('ai_summary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_inferential_analyses', function (Blueprint $table) {
            if (Schema::hasColumn('survey_inferential_analyses', 'is_included_in_report')) {
                $table->dropColumn('is_included_in_report');
            }
        });
    }
};
