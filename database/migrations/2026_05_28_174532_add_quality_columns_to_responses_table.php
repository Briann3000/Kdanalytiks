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
        Schema::table('responses', function (Blueprint $table) {
            $table->integer('quality_score')->nullable()->after('ai_metadata');
            $table->json('quality_flags')->nullable()->after('quality_score');
            $table->boolean('is_flagged')->default(false)->after('quality_flags');
            $table->integer('completion_time_seconds')->nullable()->after('is_flagged');
            $table->string('ip_address')->nullable()->after('completion_time_seconds');
            $table->string('device_fingerprint')->nullable()->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn([
                'quality_score',
                'quality_flags',
                'is_flagged',
                'completion_time_seconds',
                'ip_address',
                'device_fingerprint',
            ]);
        });
    }
};
