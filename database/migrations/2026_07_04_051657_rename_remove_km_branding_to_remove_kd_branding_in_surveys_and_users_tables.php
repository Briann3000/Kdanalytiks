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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('surveys', function (Blueprint $table) {
                $table->renameColumn('remove_km_branding', 'remove_kd_branding');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('remove_km_branding', 'remove_kd_branding');
            });
        } else {
            Illuminate\Support\Facades\DB::statement('ALTER TABLE surveys CHANGE remove_km_branding remove_kd_branding TINYINT(1) NOT NULL DEFAULT 0');
            Illuminate\Support\Facades\DB::statement('ALTER TABLE users CHANGE remove_km_branding remove_kd_branding TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('surveys', function (Blueprint $table) {
                $table->renameColumn('remove_kd_branding', 'remove_km_branding');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('remove_kd_branding', 'remove_km_branding');
            });
        } else {
            Illuminate\Support\Facades\DB::statement('ALTER TABLE surveys CHANGE remove_kd_branding remove_km_branding TINYINT(1) NOT NULL DEFAULT 0');
            Illuminate\Support\Facades\DB::statement('ALTER TABLE users CHANGE remove_kd_branding remove_km_branding TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
};
