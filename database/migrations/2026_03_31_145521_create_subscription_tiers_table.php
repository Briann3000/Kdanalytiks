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
        Schema::create('subscription_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->string('currency')->default('USD');

            // Limits
            $table->integer('max_surveys')->default(3); // free:3, pro:10, enterprise:-1
            $table->integer('max_responses_per_survey')->default(100);
            $table->integer('ai_limit_per_month')->default(10);

            // Features
            $table->boolean('has_custom_branding')->default(false);
            $table->boolean('has_data_export')->default(false);
            $table->boolean('has_advanced_analytics')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_tiers');
    }
};
