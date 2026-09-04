<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Niveles óptimos (editables) de los indicadores financieros de la hoja
     * "Calculadora" del Excel de referencia — usados por el modal "Ver
     * indicadores financieros" de ESF/ERI. `ratio_working_capital_target`
     * es un valor en pesos, el resto son razones/porcentajes.
     */
    public function up(): void
    {
        Schema::table('client_budget_data', function (Blueprint $table) {
            $table->decimal('ratio_liquidity_target', 8, 2)->default(2)->after('partner_contributions');
            $table->decimal('ratio_debt_target', 8, 4)->default(0.40)->after('ratio_liquidity_target');
            $table->decimal('ratio_interest_coverage_target', 8, 2)->default(14)->after('ratio_debt_target');
            $table->decimal('ratio_roe_target', 8, 4)->default(0.14)->after('ratio_interest_coverage_target');
            $table->decimal('ratio_roa_target', 8, 4)->default(0.14)->after('ratio_roe_target');
            $table->decimal('ratio_working_capital_target', 18, 2)->default(0)->after('ratio_roa_target');
        });
    }

    public function down(): void
    {
        Schema::table('client_budget_data', function (Blueprint $table) {
            $table->dropColumn([
                'ratio_liquidity_target', 'ratio_debt_target', 'ratio_interest_coverage_target',
                'ratio_roe_target', 'ratio_roa_target', 'ratio_working_capital_target',
            ]);
        });
    }
};
