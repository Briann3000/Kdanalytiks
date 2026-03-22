<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('survey_permissions', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('user_id');
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('survey_permissions', function (Blueprint $table) {
            $table->string('role')->after('user_id')->nullable();
            $table->dropColumn('permissions');
        });
    }
};
