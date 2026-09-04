<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSettlement extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'employee_id',
        'smlv',
        'last_salary',
        'basic_salary',
        'transport_allowance_input',
        'worked_days_month',
        'indemnification_applies',
        'contract_type',
        'year_start_date',
        'vacation_period_start',
        'prima_period_start',
        'contract_end_date',
        'contract_reference_date',
        'prima_base',
        'prima_days',
        'prima_value',
        'cesantias_base',
        'cesantias_days',
        'cesantias_value',
        'interest_cesantias_value',
        'vacation_base',
        'vacation_days',
        'vacation_value',
        'basic_salary_pay',
        'overtime_value',
        'recargos_value',
        'commissions',
        'bonuses_salarial',
        'per_diem_salarial',
        'other_salarial',
        'occasional_bonuses',
        'extralegal_premiums',
        'per_diem_no_salarial',
        'transport_allowance_value',
        'other_no_salarial',
        'ibc_salarial',
        'ibc_no_salarial',
        'ibc_excess',
        'ibc',
        'health_employee',
        'pension_employee',
        'fsp_employee',
        'withholding_tax',
        'other_deductions',
        'indemnification_value',
        'total_to_pay',
        'total_deductions',
        'net_pay',
    ];

    protected $casts = [
        'smlv' => 'decimal:2',
        'last_salary' => 'decimal:2',
        'basic_salary' => 'decimal:2',
        'transport_allowance_input' => 'decimal:2',
        'worked_days_month' => 'decimal:2',
        'indemnification_applies' => 'boolean',
        'year_start_date' => 'date',
        'vacation_period_start' => 'date',
        'prima_period_start' => 'date',
        'contract_end_date' => 'date',
        'contract_reference_date' => 'date',
        'prima_base' => 'decimal:2',
        'prima_days' => 'decimal:2',
        'prima_value' => 'decimal:2',
        'cesantias_base' => 'decimal:2',
        'cesantias_days' => 'decimal:2',
        'cesantias_value' => 'decimal:2',
        'interest_cesantias_value' => 'decimal:2',
        'vacation_base' => 'decimal:2',
        'vacation_days' => 'decimal:2',
        'vacation_value' => 'decimal:2',
        'basic_salary_pay' => 'decimal:2',
        'overtime_value' => 'decimal:2',
        'recargos_value' => 'decimal:2',
        'commissions' => 'decimal:2',
        'bonuses_salarial' => 'decimal:2',
        'per_diem_salarial' => 'decimal:2',
        'other_salarial' => 'decimal:2',
        'occasional_bonuses' => 'decimal:2',
        'extralegal_premiums' => 'decimal:2',
        'per_diem_no_salarial' => 'decimal:2',
        'transport_allowance_value' => 'decimal:2',
        'other_no_salarial' => 'decimal:2',
        'ibc_salarial' => 'decimal:2',
        'ibc_no_salarial' => 'decimal:2',
        'ibc_excess' => 'decimal:2',
        'ibc' => 'decimal:2',
        'health_employee' => 'decimal:2',
        'pension_employee' => 'decimal:2',
        'fsp_employee' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'indemnification_value' => 'decimal:2',
        'total_to_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
