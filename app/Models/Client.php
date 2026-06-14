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

    const TAX_OBLIGATIONS = [
        'IVA'        => 'IVA – Impuesto sobre las ventas',
        'Retefuente' => 'Retefuente – Agente de retención',
        'ICA'        => 'ICA – Industria y Comercio',
        'Renta'      => 'Renta – Impuesto sobre la renta',
        'Patrimonio' => 'Declaración de patrimonio',
        'INC'        => 'INC – Impoconsumo',
        'Exogena'    => 'Información exógena',
        'Avisos'     => 'Avisos y tableros',
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
    ];

    protected $casts = [
        'tax_responsibilities' => 'array',
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

    public function getFullDocumentAttribute(): string
    {
        return $this->dv ? "{$this->document_number}-{$this->dv}" : $this->document_number;
    }
}
