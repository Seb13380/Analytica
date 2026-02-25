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
        Schema::table('statements', function (Blueprint $table) {
            $table->string('import_status')->default('queued');
            $table->unsignedInteger('transactions_imported')->default(0);
            $table->boolean('ocr_used')->default(false);
            $table->text('import_error')->nullable();
            $table->text('extracted_text')->nullable();

            $table->index(['bank_account_id', 'import_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->dropIndex(['bank_account_id', 'import_status']);
            $table->dropColumn([
                'import_status',
                'transactions_imported',
                'ocr_used',
                'import_error',
                'extracted_text',
            ]);
        });
    }
};
