<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetClientVariable extends Model
{
    protected $table = 'budget_client_variables';

    protected $fillable = [
        'user_id', 'client_id',
        'ipc', 'inflation', 'smmlv_increase',
        'sales_growth', 'sales_growth_monthly', 'new_clients_pct',
        'payroll_growth', 'rent_growth', 'utilities_growth',
        'purchases_growth', 'interest_rate', 'services_growth',
        'custom_variables',
    ];

    protected $casts = [
        'custom_variables' => 'array',
        'ipc'                  => 'float',
        'inflation'            => 'float',
        'smmlv_increase'       => 'float',
        'sales_growth'         => 'float',
        'sales_growth_monthly' => 'float',
        'new_clients_pct'      => 'float',
        'payroll_growth'       => 'float',
        'rent_growth'          => 'float',
        'utilities_growth'     => 'float',
        'purchases_growth'     => 'float',
        'interest_rate'        => 'float',
        'services_growth'      => 'float',
    ];

    public static function labels(): array
    {
        return [
            'ipc'                  => 'IPC proyectado',
            'inflation'            => 'Inflación proyectada',
            'smmlv_increase'       => 'Incremento SMMLV',
            'sales_growth'         => 'Meta crecimiento ventas (anual)',
            'sales_growth_monthly' => 'Meta crecimiento ventas (mensual)',
            'new_clients_pct'      => 'Meta nuevos clientes (%)',
            'payroll_growth'       => 'Incremento nómina',
            'rent_growth'          => 'Incremento arrendamientos',
            'utilities_growth'     => 'Incremento servicios públicos',
            'purchases_growth'     => 'Incremento compras',
            'interest_rate'        => 'Tasa de interés bancaria',
            'services_growth'      => 'Incremento tarifas de servicios',
        ];
    }

    public function getRateByDriver(string $driver): float
    {
        return match ($driver) {
            'ipc'                  => $this->ipc,
            'inflation'            => $this->inflation,
            'smmlv'                => $this->smmlv_increase,
            'sales_growth'         => $this->sales_growth,
            'sales_growth_monthly' => $this->sales_growth_monthly,
            'payroll_growth'       => $this->payroll_growth,
            'rent_growth'          => $this->rent_growth,
            'utilities_growth'     => $this->utilities_growth,
            'purchases_growth'     => $this->purchases_growth,
            'interest_rate'        => $this->interest_rate,
            'services_growth'      => $this->services_growth,
            default                => 0.0,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
