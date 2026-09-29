<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('last4', 4)->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->unsignedTinyInteger('closing_day')->default(1);
            $table->unsignedTinyInteger('due_day')->default(10);
            $table->string('color')->nullable(); // gradiente css
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
