<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pareja ESF↔ERI (autorreferenciada, simétrica: cualquiera de los dos
     * puede apuntar al otro) — misma mecánica que `linked_sales_budget_id`
     * de Flujo de Caja, pero un solo campo porque la relación es 1:1 mutua.
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreignId('linked_counterpart_budget_id')->nullable()->after('linked_payroll_budget_id')
                ->constrained('budgets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_counterpart_budget_id');
        });
    }
};
