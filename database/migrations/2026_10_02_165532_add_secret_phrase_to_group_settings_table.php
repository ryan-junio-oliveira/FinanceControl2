<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_settings', function (Blueprint $table) {
            $table->string('secret_phrase', 80)->nullable()->after('notifications');
        });
    }

    public function down(): void
    {
        Schema::table('group_settings', function (Blueprint $table) {
            $table->dropColumn('secret_phrase');
        });
    }
};
