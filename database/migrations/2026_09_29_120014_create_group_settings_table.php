<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->unique()->constrained('groups')->cascadeOnDelete();
            $table->string('currency', 3)->default('BRL');
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->boolean('consolidate_dependent_yield')->default(true);
            $table->json('notifications')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_settings');
    }
};
