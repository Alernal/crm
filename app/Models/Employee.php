<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    const CONTRACT_TYPES = [
        'indefinido' => 'Término indefinido',
        'fijo' => 'Término fijo',
        'obra_labor' => 'Obra o labor',
        'aprendizaje' => 'Contrato de aprendizaje',
    ];

    const TRANSPORT_ALLOWANCE_MODES = [
        'automatico' => 'Automático (según tope legal)',
        'siempre' => 'Siempre otorgarlo',
        'nunca' => 'Nunca otorgarlo',
    ];

    const ARL_RISK_LEVELS = [
        'I' => 'I - Riesgo mínimo',
        'II' => 'II - Riesgo bajo',
        'III' => 'III - Riesgo medio',
        'IV' => 'IV - Riesgo alto',
        'V' => 'V - Riesgo máximo',
    ];

    const TERMINATION_REASONS = [
        'renuncia' => 'Renuncia',
        'despido_con_justa_causa' => 'Despido con justa causa',
        'despido_sin_justa_causa' => 'Despido sin justa causa',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'email',
        'phone',
        'address',
        'position',
        'contract_type',
        'hire_date',
        'termination_date',
        'termination_reason',
        'base_salary',
        'salary_type',
        'weekly_hours_override',
        'transport_allowance_mode',
        'transport_allowance_note',
        'eps',
        'afp',
        'arl_risk_level',
        'pension_exempt',
        'vacation_opening_balance_days',
        'vacation_opening_balance_date',
        'ccf',
        'bank_name',
        'account_type',
        'account_number',
        'status',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'base_salary' => 'decimal:2',
        'weekly_hours_override' => 'decimal:2',
        'pension_exempt' => 'boolean',
        'vacation_opening_balance_days' => 'decimal:2',
        'vacation_opening_balance_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function contractSettlements(): HasMany
    {
        return $this->hasMany(ContractSettlement::class);
    }

    public function vacationPeriods(): HasMany
    {
        return $this->hasMany(EmployeeVacationPeriod::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
