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
        Schema::table('reports', function (Blueprint $table) {
            $table->string('hash_integrity', 64)->nullable()->after('file_path');
            $table->string('original_filename')->nullable()->after('hash_integrity');
            $table->string('mime_type')->nullable()->after('original_filename');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
            $table->string('encryption_alg')->default('AES-256-GCM')->after('size_bytes');
            $table->json('encryption_meta')->nullable()->after('encryption_alg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'hash_integrity',
                'original_filename',
                'mime_type',
                'size_bytes',
                'encryption_alg',
                'encryption_meta',
            ]);
        });
    }
};
