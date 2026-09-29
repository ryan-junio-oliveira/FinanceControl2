<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('family_id')->nullable()->after('id')->constrained('families')->nullOnDelete();
            $table->string('role')->default('dependente')->after('password');
            $table->string('phone')->nullable()->after('role');
            $table->date('birthdate')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_id');
            $table->dropColumn(['role', 'phone', 'birthdate']);
        });
    }
};
