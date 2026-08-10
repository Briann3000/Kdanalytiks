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
        Schema::create('org_survey_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // the enumerator
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->integer('response_quota')->nullable(); // NULL means unlimited
            $table->string('zone_label', 100)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'survey_id', 'user_id'], 'unique_org_survey_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_survey_assignments');
    }
};
