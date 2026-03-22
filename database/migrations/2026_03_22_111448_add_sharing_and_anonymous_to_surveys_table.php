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
        Schema::table('surveys', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('status');
            $table->string('public_access')->default('none')->after('is_anonymous'); // none, view, submit, edit
            $table->string('share_token', 32)->nullable()->unique()->after('public_access');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['is_anonymous', 'public_access', 'share_token']);
        });
    }
};
