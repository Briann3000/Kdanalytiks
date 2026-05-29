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
        Schema::create('survey_invite_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('survey_invite_campaigns')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('token')->unique();
            $table->string('status')->default('pending'); // pending, sent, opened, responded, bounced
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->dateTime('last_reminder_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'email']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_invite_recipients');
    }
};
