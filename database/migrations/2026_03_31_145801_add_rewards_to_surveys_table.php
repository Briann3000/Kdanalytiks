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
        Schema::table('surveys', function (Blueprint $table) {
            $table->decimal('reward_per_response', 15, 2)->default(0)->after('json_schema');
            $table->decimal('reward_budget', 15, 2)->default(0)->after('reward_per_response');
            $table->decimal('current_reward_spent', 15, 2)->default(0)->after('reward_budget');
            $table->string('reward_currency')->default('KES')->after('current_reward_spent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['reward_per_response', 'reward_budget', 'current_reward_spent', 'reward_currency']);
        });
    }
};
