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
            if (!Schema::hasColumn('users', 'transcription_count')) {
                $table->integer('transcription_count')->default(0)->after('free_export_count');
            }
            if (!Schema::hasColumn('users', 'proofread_count')) {
                $table->integer('proofread_count')->default(0)->after('transcription_count');
            }
            if (!Schema::hasColumn('users', 'free_report_export_count')) {
                $table->integer('free_report_export_count')->default(0)->after('proofread_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['transcription_count', 'proofread_count', 'free_report_export_count']);
        });
    }
};
