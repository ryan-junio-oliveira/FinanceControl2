<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('credit_card_id')->constrained('credit_cards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->date('occurred_on');
            $table->string('status')->default('pendente'); // pago|pendente
            $table->timestamps();

            $table->index(['family_id', 'credit_card_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
