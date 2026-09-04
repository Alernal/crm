<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Datos iniciales requeridos
            $table->decimal('smlv', 12, 2);
            $table->decimal('last_salary', 12, 2);
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('transport_allowance_input', 12, 2)->default(0);
            $table->decimal('worked_days_month', 5, 2);
            $table->boolean('indemnification_applies')->default(false);
            $table->string('contract_type', 20);

            // Fechas requeridas en los cálculos
            $table->date('year_start_date');
            $table->date('vacation_period_start');
            $table->date('prima_period_start');
            $table->date('contract_end_date');
            $table->date('contract_reference_date');

            // Prestaciones sociales (calculadas)
            $table->decimal('prima_base', 12, 2);
            $table->decimal('prima_days', 6, 2);
            $table->decimal('prima_value', 14, 2);
            $table->decimal('cesantias_base', 12, 2);
            $table->decimal('cesantias_days', 6, 2);
            $table->decimal('cesantias_value', 14, 2);
            $table->decimal('interest_cesantias_value', 14, 2);
            $table->decimal('vacation_base', 12, 2);
            $table->decimal('vacation_days', 6, 2);
            $table->decimal('vacation_value', 14, 2);

            // Pagos salariales del último período (manuales + calculado)
            $table->decimal('basic_salary_pay', 12, 2);
            $table->decimal('overtime_value', 12, 2)->default(0);
            $table->decimal('recargos_value', 12, 2)->default(0);
            $table->decimal('commissions', 12, 2)->default(0);
            $table->decimal('bonuses_salarial', 12, 2)->default(0);
            $table->decimal('per_diem_salarial', 12, 2)->default(0);
            $table->decimal('other_salarial', 12, 2)->default(0);

            // Pagos no salariales del último período (manuales + calculado)
            $table->decimal('occasional_bonuses', 12, 2)->default(0);
            $table->decimal('extralegal_premiums', 12, 2)->default(0);
            $table->decimal('per_diem_no_salarial', 12, 2)->default(0);
            $table->decimal('transport_allowance_value', 12, 2)->default(0);
            $table->decimal('other_no_salarial', 12, 2)->default(0);

            // IBC
            $table->decimal('ibc_salarial', 14, 2);
            $table->decimal('ibc_no_salarial', 14, 2);
            $table->decimal('ibc_excess', 14, 2);
            $table->decimal('ibc', 14, 2);

            // Deducciones del trabajador
            $table->decimal('health_employee', 12, 2);
            $table->decimal('pension_employee', 12, 2);
            $table->decimal('fsp_employee', 12, 2);
            $table->decimal('withholding_tax', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);

            // Indemnización
            $table->decimal('indemnification_value', 14, 2)->default(0);

            // Resumen
            $table->decimal('total_to_pay', 14, 2);
            $table->decimal('total_deductions', 14, 2);
            $table->decimal('net_pay', 14, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_settlements');
    }
};
