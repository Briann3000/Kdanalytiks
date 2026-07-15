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
        Schema::table('survey_ai_threads', function (Blueprint $table) {
            $table->foreignId('survey_group_id')->nullable()->constrained('survey_groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_ai_threads', function (Blueprint $table) {
            $table->dropForeign(['survey_group_id']);
            $table->dropColumn('survey_group_id');
        });
    }
};
