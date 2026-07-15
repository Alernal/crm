<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxEvent extends Model
{
    const OBLIGATION_TYPES = [
        // Códigos legacy (sistema anterior)
        'IVA'              => 'IVA',
        'IVA_anual'        => 'IVA Anual',
        'Retefuente'       => 'Retefuente',
        'Renta'            => 'Renta',
        'ICA'              => 'ICA',
        'Patrimonio'       => 'Patrimonio',
        'Activos_Exterior' => 'Activos en el Exterior',
        'INC'              => 'Impoconsumo',
        'Exogena'          => 'Información Exógena',
        'SIMPLE_anticipo'  => 'SIMPLE — Anticipo bimestral',
        'SIMPLE_anual'     => 'SIMPLE — Dec. anual consolidada',
        'Otro'             => 'Otro',
        // Códigos admin (TaxCalendarService)
        'IVA_BI'    => 'IVA Bimestral',
        'IVA_C4'    => 'IVA Cuatrimestral',
        'RTEFTE'    => 'Retefuente Mensual',
        'RENTA_NAT' => 'Renta Personas Naturales',
        'RENTA_JUR' => 'Renta Personas Jurídicas',
        'EXOGENA'   => 'Información Exógena',
        'SIMPLE_ANT'=> 'Anticipo Bimestral SIMPLE',
        'SIMPLE_IVA'=> 'IVA Anual Régimen SIMPLE',
        'SIMPLE_DEC'=> 'Declaración Anual Consolidada SIMPLE',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'obligation_type',
        'source',
        'due_date',
        'period',
        'alert_days',
        'status',
        'is_recurring',
        'recurrence_pattern',
        'notes',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'is_recurring' => 'boolean',
        'alert_days'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
