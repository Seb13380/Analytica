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
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('label');
            $table->text('normalized_label')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('type');
            $table->decimal('balance_after', 14, 2)->nullable();
            $table->boolean('beneficiary_detected')->default(false);
            $table->unsignedSmallInteger('anomaly_score')->nullable();
            $table->string('anomaly_level')->nullable();
            $table->json('rule_flags')->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'date']);
            $table->unique(['bank_account_id', 'date', 'amount', 'type', 'normalized_label'], 'transactions_dedupe');
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
