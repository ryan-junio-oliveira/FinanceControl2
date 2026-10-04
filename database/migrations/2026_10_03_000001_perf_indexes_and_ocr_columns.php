<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OCR assíncrono: status + texto + payload parseado (polling via API).
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('ocr_status', 20)->default('pending')->after('size');
            $table->text('ocr_text')->nullable()->after('ocr_status');
            $table->json('ocr_data')->nullable()->after('ocr_text');
        });

        // Índices para filtros quentes (extratos, dashboard por membro, faturas).
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['group_id', 'user_id', 'occurred_on'], 'transactions_group_user_date_idx');
            $table->index(['group_id', 'account_id', 'status'], 'transactions_group_account_status_idx');
            $table->index(['group_id', 'category_id'], 'transactions_group_category_idx');
        });

        Schema::table('card_transactions', function (Blueprint $table) {
            $table->index(['group_id', 'status', 'occurred_on'], 'card_tx_group_status_date_idx');
            $table->index(['group_id', 'user_id'], 'card_tx_group_user_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['group_id', 'action'], 'audit_group_action_idx');
            $table->index(['group_id', 'user_id'], 'audit_group_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_group_action_idx');
            $table->dropIndex('audit_group_user_idx');
        });
        Schema::table('card_transactions', function (Blueprint $table) {
            $table->dropIndex('card_tx_group_status_date_idx');
            $table->dropIndex('card_tx_group_user_idx');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_group_user_date_idx');
            $table->dropIndex('transactions_group_account_status_idx');
            $table->dropIndex('transactions_group_category_idx');
        });
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn(['ocr_status', 'ocr_text', 'ocr_data']);
        });
    }
};
