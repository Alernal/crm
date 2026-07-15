<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->decimal('worked_days', 5, 2)->default(30);

            // Pagos salariales
            $table->decimal('basic_salary_pay', 14, 2)->default(0);
            $table->decimal('overtime_pay', 14, 2)->default(0);
            $table->decimal('commissions', 14, 2)->default(0);
            $table->decimal('bonuses_salarial', 14, 2)->default(0);
            $table->decimal('per_diem_salarial', 14, 2)->default(0);
            $table->decimal('other_salarial', 14, 2)->default(0);
            $table->decimal('subtotal_salarial', 14, 2)->default(0);

            // Pagos no salariales
            $table->decimal('transport_allowance', 14, 2)->default(0);
            $table->decimal('occasional_bonuses', 14, 2)->default(0);
            $table->decimal('extralegal_premiums', 14, 2)->default(0);
            $table->decimal('per_diem_no_salarial', 14, 2)->default(0);
            $table->decimal('other_no_salarial', 14, 2)->default(0);
            $table->decimal('subtotal_no_salarial', 14, 2)->default(0);

            $table->decimal('total_earned', 14, 2)->default(0);

            // Seguridad social
            $table->decimal('ibc', 14, 2)->default(0);
            $table->decimal('health_employee', 14, 2)->default(0);
            $table->decimal('pension_employee', 14, 2)->default(0);
            $table->decimal('fsp_employee', 14, 2)->default(0);
            $table->decimal('loans_deduction', 14, 2)->default(0);
            $table->decimal('withholding_tax', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);

            // Aportes del empleador
            $table->decimal('health_employer', 14, 2)->default(0);
            $table->decimal('pension_employer', 14, 2)->default(0);
            $table->decimal('arl_employer', 14, 2)->default(0);
            $table->decimal('caja_compensacion', 14, 2)->default(0);
            $table->decimal('icbf', 14, 2)->default(0);
            $table->decimal('sena', 14, 2)->default(0);
            $table->decimal('total_employer_contributions', 14, 2)->default(0);

            // Provisión de prestaciones sociales
            $table->decimal('prima_provision', 14, 2)->default(0);
            $table->decimal('cesantias_provision', 14, 2)->default(0);
            $table->decimal('interest_cesantias_provision', 14, 2)->default(0);
            $table->decimal('vacation_provision', 14, 2)->default(0);

            $table->decimal('net_pay', 14, 2)->default(0);

            $table->enum('status', ['borrador', 'emitida', 'pagada', 'anulada'])->default('borrador');
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
