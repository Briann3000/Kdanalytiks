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
        Schema::table('subscription_tiers', function (Blueprint $table) {
            $table->integer('org_max_seats')->default(2)->after('max_surveys');
            $table->integer('org_ai_analyses_per_month')->default(5)->after('org_max_seats');
            $table->integer('org_transcription_minutes_per_month')->default(2)->after('org_ai_analyses_per_month');
            $table->integer('org_socius_sessions_per_month')->default(20)->after('org_transcription_minutes_per_month');
            $table->integer('org_report_exports_per_month')->default(5)->after('org_socius_sessions_per_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_tiers', function (Blueprint $table) {
            $table->dropColumn([
                'org_max_seats',
                'org_ai_analyses_per_month',
                'org_transcription_minutes_per_month',
                'org_socius_sessions_per_month',
                'org_report_exports_per_month',
            ]);
        });
    }
};
