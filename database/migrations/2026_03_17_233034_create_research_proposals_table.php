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
        Schema::create('research_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('research_question');
            $table->text('objectives');
            $table->string('methodology_type'); // survey, qualitative, mixed
            $table->text('scope')->nullable();
            $table->string('style')->default('apa7');
            $table->json('content')->nullable(); // Stores the generated sections
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_proposals');
    }
};
