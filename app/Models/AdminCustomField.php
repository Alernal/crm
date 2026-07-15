<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminCustomField extends Model
{
    protected $table = 'admin_custom_fields';

    protected $fillable = [
        'module_key', 'name', 'label', 'type', 'required', 'visible',
        'active', 'order', 'default_value', 'validation_rules',
    ];

    protected $casts = [
        'required'         => 'boolean',
        'visible'          => 'boolean',
        'active'           => 'boolean',
        'validation_rules' => 'array',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(AdminModule::class, 'module_key', 'key');
    }

    public static array $types = [
        'text'     => 'Texto',
        'number'   => 'Número',
        'email'    => 'Correo electrónico',
        'date'     => 'Fecha',
        'select'   => 'Lista desplegable',
        'checkbox' => 'Casilla de verificación',
        'textarea' => 'Área de texto',
        'phone'    => 'Teléfono',
        'nit'      => 'NIT',
    ];
}
