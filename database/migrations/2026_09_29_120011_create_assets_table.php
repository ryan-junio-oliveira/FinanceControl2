<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained('portfolios')->nullOnDelete();
            $table->string('code'); // ticker / identificador
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('holder')->nullable();
            $table->string('kind')->default('renda_fixa'); // renda_fixa|fii|acao|etf|previdencia
            $table->decimal('current_value', 14, 2)->default(0);
            $table->decimal('yield_percent', 8, 2)->nullable();
            $table->string('yield_base', 20)->default('cdi'); // cdi|selic|ipca|prefixado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
