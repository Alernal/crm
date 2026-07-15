<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    const TAX_RESPONSIBILITIES = [
        'gran_contribuyente'   => 'Gran Contribuyente',
        'autorretenedor'       => 'Autorretenedor',
        'agente_retencion_iva' => 'Agente de Retención de IVA',
        'regimen_simple'       => 'Régimen Simple de Tributación',
        'no_aplica'            => 'No Aplica',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'document_type',
        'document_number',
        'dv',
        'person_type',
        'tax_regime',
        'tax_responsibilities',
        'email',
        'phone',
        'address',
        'city',
        'department',
        'contact_person',
        'status',
        'notes',
        'invoice_prefix',
        'invoice_consecutive',
        'payroll_periodicity',
        'payroll_prefix',
        'payroll_consecutive',
        'payroll_pila_exempt',
    ];

    protected $casts = [
        'tax_responsibilities' => 'array',
        'payroll_pila_exempt' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function taxEvents(): HasMany
    {
        return $this->hasMany(TaxEvent::class);
    }

    public function virtualFolders(): HasMany
    {
        return $this->hasMany(VirtualFolder::class);
    }

    public function virtualFiles(): HasMany
    {
        return $this->hasMany(VirtualFile::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function primaSettlements(): HasMany
    {
        return $this->hasMany(PrimaSettlement::class);
    }

    public function cesantiaSettlements(): HasMany
    {
        return $this->hasMany(CesantiaSettlement::class);
    }

    public function archiveRootName(): string
    {
        $doc = $this->document_number;
        if ($this->dv) $doc .= '-' . $this->dv;
        return "{$doc} - {$this->name}";
    }

    public function getFullDocumentAttribute(): string
    {
        return $this->dv ? "{$this->document_number}-{$this->dv}" : $this->document_number;
    }
}
