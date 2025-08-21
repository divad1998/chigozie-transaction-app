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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->enum('type', ['credit','debit']);
            $table->decimal('amount', 20, 4);
            $table->string('currency')->default('NGN');
            $table->string('description');
            $table->timestamps();

            $table->unique(['wallet_id','reference'], 'txn_wallet_reference_unique');
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
