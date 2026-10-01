<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->nullable(); // código de compensação (ex.: 001)
            $table->string('name'); // ex.: Banco do Brasil S.A.
            $table->string('color', 7)->nullable(); // cor da marca (ex.: #820AD1)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
