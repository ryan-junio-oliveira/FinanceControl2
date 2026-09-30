<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('installment_group_id')->nullable()->after('notes');
            $table->unsignedTinyInteger('installment_number')->nullable()->after('installment_group_id');
            $table->unsignedTinyInteger('installments_total')->nullable()->after('installment_number');
            $table->index('installment_group_id');
        });

        Schema::table('card_transactions', function (Blueprint $table) {
            $table->uuid('installment_group_id')->nullable()->after('status');
            $table->unsignedTinyInteger('installment_number')->nullable()->after('installment_group_id');
            $table->unsignedTinyInteger('installments_total')->nullable()->after('installment_number');
            $table->index('installment_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('card_transactions', function (Blueprint $table) {
            $table->dropIndex(['installment_group_id']);
            $table->dropColumn(['installment_group_id', 'installment_number', 'installments_total']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['installment_group_id']);
            $table->dropColumn(['installment_group_id', 'installment_number', 'installments_total']);
        });
    }
};
