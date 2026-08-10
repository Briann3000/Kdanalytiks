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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug', 100)->unique()->nullable()->after('name');
            $table->string('industry', 100)->nullable()->after('slug');
            $table->string('logo_url', 500)->nullable()->after('industry');
            $table->string('brand_color', 10)->default('#2271b1')->nullable()->after('logo_url');
            $table->string('website_url', 255)->nullable()->after('brand_color');
            $table->string('country', 100)->nullable()->after('website_url');
            $table->boolean('enforce_branding')->default(false)->after('country');
            $table->string('custom_email_domain', 255)->nullable()->after('enforce_branding');
            $table->boolean('email_domain_verified')->default(false)->after('custom_email_domain');
            $table->boolean('pii_mask_by_default')->default(false)->after('email_domain_verified');
            $table->integer('max_seats')->default(2)->after('pii_mask_by_default');
            $table->boolean('survey_approval_required')->default(false)->after('max_seats');
            $table->integer('data_retention_days')->nullable()->after('survey_approval_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'industry',
                'logo_url',
                'brand_color',
                'website_url',
                'country',
                'enforce_branding',
                'custom_email_domain',
                'email_domain_verified',
                'pii_mask_by_default',
                'max_seats',
                'survey_approval_required',
                'data_retention_days',
            ]);
        });
    }
};
