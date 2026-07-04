<?php
// d:\kdanalytiks\database\migrations\2026_04_14_083700_add_subscription_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subscription_tier_id')->nullable()->constrained('subscription_tiers')->onDelete('set null');
            $table->timestamp('subscription_expiry')->nullable();
            $table->string('payment_status')->default('unpaid')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subscription_tier_id']);
            $table->dropColumn(['subscription_tier_id', 'subscription_expiry', 'payment_status']);
        });
    }
};
