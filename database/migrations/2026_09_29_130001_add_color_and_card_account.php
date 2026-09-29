<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('color', 9)->nullable()->after('kind');
        });

        Schema::table('credit_cards', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('holder_user_id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
