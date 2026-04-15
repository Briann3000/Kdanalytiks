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
        Schema::table('independents', function (Blueprint $table) {
            $table->foreignId('subscription_tier_id')->nullable()->after('user_id')->constrained('subscription_tiers');
            $table->integer('ai_usage_monthly')->default(0)->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('independents', function (Blueprint $table) {
            $table->dropForeign(['subscription_tier_id']);
            $table->dropColumn(['subscription_tier_id', 'ai_usage_monthly']);
        });
    }
};
