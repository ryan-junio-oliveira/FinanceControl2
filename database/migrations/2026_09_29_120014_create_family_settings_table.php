<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->unique()->constrained('families')->cascadeOnDelete();
            $table->string('currency', 3)->default('BRL');
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->unsignedTinyInteger('closing_day')->default(1);
            $table->decimal('approval_threshold', 12, 2)->default(500);
            $table->decimal('privacy_hide_under', 12, 2)->default(50);
            $table->boolean('consolidate_dependent_yield')->default(true);
            $table->json('notifications')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_settings');
    }
};
