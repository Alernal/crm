<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simplifica el submódulo Presupuestos a un solo tipo autocontenido
     * (Flujo de Caja): elimina los presupuestos de Ventas y de Gastos
     * Administrativos y de Ventas (cascada ya configurada sobre secciones,
     * líneas, valores y activos de inversión) y las columnas que solo ellos
     * usaban.
     */
    public function up(): void
    {
        DB::table('budgets')->whereIn('type', ['ventas', 'nomina'])->delete();

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_sales_budget_id');
            $table->dropConstrainedForeignId('linked_payroll_budget_id');
            $table->dropColumn(['sales_mode', 'arl_risk_level']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->enum('type', ['flujo_caja', 'esf', 'eri'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->enum('type', ['ventas', 'flujo_caja', 'nomina', 'esf', 'eri'])->change();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->enum('sales_mode', ['unidades', 'general'])->nullable()->after('type');
            $table->string('arl_risk_level', 3)->nullable()->default('I')->after('sales_mode');
            $table->foreignId('linked_sales_budget_id')->nullable()->after('arl_risk_level')
                ->constrained('budgets')->nullOnDelete();
            $table->foreignId('linked_payroll_budget_id')->nullable()->after('linked_sales_budget_id')
                ->constrained('budgets')->nullOnDelete();
        });
    }
};
