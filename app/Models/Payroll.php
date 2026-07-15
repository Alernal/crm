<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    const STATUSES = [
        'borrador' => 'Borrador',
        'emitida' => 'Emitida',
        'pagada' => 'Pagada',
        'anulada' => 'Anulada',
    ];

    /** Conceptos de devengo para la impresión resumida de la tabla de nómina. */
    const PRINT_DEVENGO_FIELDS = [
        'basic_salary_pay' => 'Salario básico',
        'overtime_pay' => 'Horas extra y recargos',
        'commissions' => 'Comisiones',
        'bonuses_salarial' => 'Bonificaciones salariales',
        'per_diem_salarial' => 'Viáticos permanentes',
        'other_salarial' => 'Otros pagos salariales',
        'transport_allowance' => 'Auxilio de transporte',
        'occasional_bonuses' => 'Bonificaciones ocasionales',
        'extralegal_premiums' => 'Primas extralegales',
        'per_diem_no_salarial' => 'Viáticos (no salariales)',
        'other_no_salarial' => 'Otros pagos no salariales',
    ];

    /**
     * Conceptos de descuento para la impresión resumida de la tabla de
     * nómina. Salud y pensión del trabajador SÍ se incluyen (son
     * necesarios para sustentar el neto a pagar); el resto de la
     * seguridad social (FSP, aportes patronales) y las prestaciones
     * sociales quedan fuera para no alargar la tabla.
     */
    const PRINT_DESCUENTO_FIELDS = [
        'health_employee' => 'Salud',
        'pension_employee' => 'Pensión',
        'loans_deduction' => 'Préstamos',
        'withholding_tax' => 'Retención en la fuente',
        'other_deductions' => 'Otras deducciones',
    ];

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'user_id',
        'client_id',
        'worked_days',
        'basic_salary_pay',
        'overtime_pay',
        'commissions',
        'bonuses_salarial',
        'per_diem_salarial',
        'other_salarial',
        'subtotal_salarial',
        'transport_allowance',
        'occasional_bonuses',
        'extralegal_premiums',
        'per_diem_no_salarial',
        'other_no_salarial',
        'subtotal_no_salarial',
        'total_earned',
        'ibc',
        'health_employee',
        'pension_employee',
        'fsp_employee',
        'loans_deduction',
        'withholding_tax',
        'other_deductions',
        'total_deductions',
        'health_employer',
        'pension_employer',
        'arl_employer',
        'caja_compensacion',
        'icbf',
        'sena',
        'total_employer_contributions',
        'prima_provision',
        'cesantias_provision',
        'interest_cesantias_provision',
        'vacation_provision',
        'net_pay',
        'status',
    ];

    protected $casts = [
        'worked_days' => 'decimal:2',
        'basic_salary_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'commissions' => 'decimal:2',
        'bonuses_salarial' => 'decimal:2',
        'per_diem_salarial' => 'decimal:2',
        'other_salarial' => 'decimal:2',
        'subtotal_salarial' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'occasional_bonuses' => 'decimal:2',
        'extralegal_premiums' => 'decimal:2',
        'per_diem_no_salarial' => 'decimal:2',
        'other_no_salarial' => 'decimal:2',
        'subtotal_no_salarial' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'ibc' => 'decimal:2',
        'health_employee' => 'decimal:2',
        'pension_employee' => 'decimal:2',
        'fsp_employee' => 'decimal:2',
        'loans_deduction' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'health_employer' => 'decimal:2',
        'pension_employer' => 'decimal:2',
        'arl_employer' => 'decimal:2',
        'caja_compensacion' => 'decimal:2',
        'icbf' => 'decimal:2',
        'sena' => 'decimal:2',
        'total_employer_contributions' => 'decimal:2',
        'prima_provision' => 'decimal:2',
        'cesantias_provision' => 'decimal:2',
        'interest_cesantias_provision' => 'decimal:2',
        'vacation_provision' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function overtimeItems(): HasMany
    {
        return $this->hasMany(PayrollOvertimeItem::class);
    }
}
