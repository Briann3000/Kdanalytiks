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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('type'); // credit, debit
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->string('description')->nullable();
            $table->string('reference')->unique(); // Internal reference
            $table->string('external_reference')->nullable(); // IntaSend/Daraja/PayPal reference
            $table->json('metadata')->nullable(); // For any extra data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
