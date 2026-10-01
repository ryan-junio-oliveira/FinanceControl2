<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // membro responsável
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type'); // receita|despesa|transferencia|aporte
            $table->boolean('is_fixed')->default(false); // lançamento fixo mensal
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->date('occurred_on');
            $table->date('due_on')->nullable();
            $table->string('status')->default('pago'); // pago|pendente|agendado
            $table->foreignId('transfer_to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('portfolio_id')->nullable(); // FK definida em create_contributions (portfolios ainda não existe aqui)
            $table->text('notes')->nullable();
            $table->uuid('installment_group_id')->nullable();
            $table->unsignedTinyInteger('installment_number')->nullable();
            $table->unsignedTinyInteger('installments_total')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'type', 'occurred_on']);
            $table->index(['family_id', 'status', 'due_on']);
            $table->index('installment_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
