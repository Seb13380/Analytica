<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_alias_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('normalized_label', 500);
            $table->string('identity_key', 100);
            $table->string('identity_label', 255);
            $table->timestamps();

            $table->unique(['case_id', 'normalized_label']);
            $table->index('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_alias_overrides');
    }
};
