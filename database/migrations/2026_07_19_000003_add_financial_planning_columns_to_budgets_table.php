<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Ventas: modo por productos/unidades vs. ventas generales.
            $table->enum('sales_mode', ['unidades', 'general'])->nullable()->after('type');

            // Nómina: nivel de riesgo ARL para el driver legal_pct 'arl'.
            $table->string('arl_risk_level', 3)->nullable()->default('I')->after('sales_mode');

            // Flujo de caja: vínculo a los presupuestos de Ventas/Nómina del
            // mismo cliente que alimentan automáticamente el Ppto al proyectar.
            $table->foreignId('linked_sales_budget_id')->nullable()->after('arl_risk_level')
                ->constrained('budgets')->nullOnDelete();
            $table->foreignId('linked_payroll_budget_id')->nullable()->after('linked_sales_budget_id')
                ->constrained('budgets')->nullOnDelete();

            // Flujo de caja: inversión inicial y plan de pagos (además de los
            // activos individuales en budget_investment_assets).
            $table->decimal('investment_initial_cash', 18, 2)->nullable()->after('linked_payroll_budget_id');
            $table->decimal('investment_partner_contributions', 18, 2)->nullable()->after('investment_initial_cash');
            $table->unsignedTinyInteger('investment_loan_term_years')->nullable()->after('investment_partner_contributions');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_sales_budget_id');
            $table->dropConstrainedForeignId('linked_payroll_budget_id');
            $table->dropColumn([
                'sales_mode', 'arl_risk_level',
                'investment_initial_cash', 'investment_partner_contributions', 'investment_loan_term_years',
            ]);
        });
    }
};
