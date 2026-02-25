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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('kind')->nullable()->after('type');
            $table->string('origin')->nullable()->after('kind');
            $table->string('destination')->nullable()->after('origin');
            $table->text('motif')->nullable()->after('destination');
            $table->string('cheque_number')->nullable()->after('motif');
            $table->json('meta')->nullable()->after('cheque_number');

            $table->index(['kind', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['kind', 'date']);
            $table->dropColumn(['kind', 'origin', 'destination', 'motif', 'cheque_number', 'meta']);
        });
    }
};
