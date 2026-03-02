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
        Schema::table('cases', function (Blueprint $table) {
            $table->text('ai_last_prompt')->nullable()->after('global_score');
            $table->json('ai_last_result')->nullable()->after('ai_last_prompt');
            $table->text('ai_last_error')->nullable()->after('ai_last_result');
            $table->timestamp('ai_last_ran_at')->nullable()->after('ai_last_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['ai_last_prompt', 'ai_last_result', 'ai_last_error', 'ai_last_ran_at']);
        });
    }
};
