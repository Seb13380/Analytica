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
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->timestamp('generated_at');
            $table->unsignedSmallInteger('global_score');
            $table->unsignedInteger('total_transactions');
            $table->unsignedInteger('total_flagged');
            $table->decimal('total_flagged_amount', 14, 2);
            $table->timestamps();

            $table->index(['case_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};
