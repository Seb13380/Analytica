<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('account_label', 120)->nullable()->after('account_type');
        });
    }
    public function down(): void {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('account_label');
        });
    }
};
