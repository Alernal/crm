<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Variables" (tasas % planas por driver) pasa a ser "Datos": los
     * indicadores año a año viven ahora en client_budget_yearly_data, y esta
     * tabla se reduce a las políticas planas del caso práctico de Actualícese
     * (Datos base filas 17-24: cartera, proveedores, socios, impuesto, reserva).
     * `interest_rate` se conserva (costo de la obligación financiera, usado por
     * el plan de pagos del flujo de caja).
     */
    public function up(): void
    {
        Schema::rename('budget_client_variables', 'client_budget_data');

        Schema::table('client_budget_data', function (Blueprint $table) {
            $table->dropColumn([
                'ipc', 'inflation', 'smmlv_increase',
                'sales_growth', 'sales_growth_monthly', 'new_clients_pct',
                'payroll_growth', 'rent_growth', 'utilities_growth',
                'purchases_growth', 'services_growth', 'custom_variables',
            ]);

            $table->decimal('credit_sales_pct', 5, 2)->default(60.00)->after('client_id');
            $table->unsignedSmallInteger('collection_days')->default(15)->after('credit_sales_pct');
            $table->unsignedSmallInteger('supplier_payment_days')->default(30)->after('collection_days');
            $table->decimal('income_tax_rate', 5, 2)->default(35.00)->after('interest_rate');
            $table->decimal('legal_reserve_pct', 5, 2)->default(10.00)->after('income_tax_rate');
            $table->decimal('partner_contributions', 18, 2)->default(0)->after('legal_reserve_pct');
        });
    }

    public function down(): void
    {
        Schema::table('client_budget_data', function (Blueprint $table) {
            $table->dropColumn([
                'credit_sales_pct', 'collection_days', 'supplier_payment_days',
                'income_tax_rate', 'legal_reserve_pct', 'partner_contributions',
            ]);

            $table->decimal('ipc', 5, 2)->default(6.77);
            $table->decimal('inflation', 5, 2)->default(5.00);
            $table->decimal('smmlv_increase', 5, 2)->default(9.54);
            $table->decimal('sales_growth', 5, 2)->default(10.00);
            $table->decimal('sales_growth_monthly', 5, 2)->default(0.80);
            $table->decimal('new_clients_pct', 5, 2)->default(5.00);
            $table->decimal('payroll_growth', 5, 2)->default(9.54);
            $table->decimal('rent_growth', 5, 2)->default(6.77);
            $table->decimal('utilities_growth', 5, 2)->default(8.00);
            $table->decimal('purchases_growth', 5, 2)->default(6.00);
            $table->decimal('services_growth', 5, 2)->default(7.00);
            $table->json('custom_variables')->nullable();
        });

        Schema::rename('client_budget_data', 'budget_client_variables');
    }
};
