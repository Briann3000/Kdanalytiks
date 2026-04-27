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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('remove_km_branding')->default(false)->after('subscription_expiry');
            $table->string('export_logo_url')->nullable()->after('remove_km_branding');
            $table->string('export_org_name')->nullable()->after('export_logo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['remove_km_branding', 'export_logo_url', 'export_org_name']);
        });
    }
};
