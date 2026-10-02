<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('source', 20)->default('cash')->after('status'); // cash|invoice
        });

        // Backfill: lançamentos gerados por fatura de cartão.
        DB::table('transactions')
            ->where('type', 'despesa')
            ->where(fn ($q) => $q->where('description', 'like', 'Fatura %')
                ->orWhere('description', 'like', 'Pagamento fatura %'))
            ->update(['source' => 'invoice']);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
