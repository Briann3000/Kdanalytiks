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
            $table->decimal('monthly_price_usd', 10, 2)->default(0)->after('yearly_price');
            $table->decimal('yearly_price_usd', 10, 2)->default(0)->after('monthly_price_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_tiers', function (Blueprint $table) {
            $table->dropColumn(['monthly_price_usd', 'yearly_price_usd']);
        });
    }
};
