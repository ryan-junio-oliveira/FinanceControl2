<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['portfolio_id']);
            $table->dropColumn('portfolio_id');
        });

        Schema::table('assets', function (Blueprint $table) {
            // Carteira agora é opcional: ativos avulsos formam a carteira geral.
            $table->foreignId('portfolio_id')->nullable()->after('family_id')
                ->constrained('portfolios')->nullOnDelete();

            $table->dropColumn('profitability');
            $table->decimal('yield_percent', 8, 2)->nullable()->after('current_value');
            $table->string('yield_base', 20)->default('cdi')->after('yield_percent');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['portfolio_id']);
            $table->dropColumn(['portfolio_id', 'yield_percent', 'yield_base']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('portfolio_id')->after('family_id')
                ->constrained('portfolios')->cascadeOnDelete();
            $table->string('profitability')->nullable()->after('current_value');
        });
    }
};
