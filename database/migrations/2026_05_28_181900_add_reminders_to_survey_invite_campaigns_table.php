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
        Schema::table('survey_invite_campaigns', function (Blueprint $table) {
            $table->boolean('auto_reminders')->default(false)->after('status');
            $table->integer('reminder_interval_days')->default(3)->after('auto_reminders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_invite_campaigns', function (Blueprint $table) {
            $table->dropColumn(['auto_reminders', 'reminder_interval_days']);
        });
    }
};
