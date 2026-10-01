<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bot_code', 6)->nullable()->unique()->after('birthdate');
        });

        Schema::create('bot_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 20); // telegram|whatsapp
            $table->string('external_id'); // chat_id / telefone
            $table->timestamps();

            $table->unique(['channel', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_identities');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bot_code');
        });
    }
};
