<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = [
        'user_id', 'client_id', 'name', 'type',
        'base_year', 'period_type', 'periods_count', 'status', 'notes',
    ];

    protected $casts = [
        'base_year'     => 'integer',
        'periods_count' => 'integer',
    ];

    const TYPES = [
        'ventas'     => 'Presupuesto de Ventas',
        'gastos'     => 'Presupuesto de Gastos',
        'compras'    => 'Presupuesto de Compras',
        'flujo_caja' => 'Flujo de Caja',
        'nomina'     => 'Presupuesto de Nómina',
    ];

    const TYPE_COLORS = [
        'ventas'     => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon_bg' => 'from-emerald-500 to-emerald-600'],
        'gastos'     => ['bg' => 'bg-red-100',     'text' => 'text-red-700',     'icon_bg' => 'from-red-500 to-rose-600'],
        'compras'    => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'icon_bg' => 'from-amber-500 to-orange-500'],
        'flujo_caja' => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',    'icon_bg' => 'from-blue-500 to-blue-700'],
        'nomina'     => ['bg' => 'bg-purple-100',  'text' => 'text-purple-700',  'icon_bg' => 'from-purple-500 to-indigo-600'],
    ];

    const STATUS_LABELS = [
        'draft'     => ['label' => 'Borrador',   'class' => 'bg-gray-100 text-gray-600'],
        'projected' => ['label' => 'Proyectado', 'class' => 'bg-blue-100 text-blue-700'],
        'final'     => ['label' => 'Aprobado',   'class' => 'bg-emerald-100 text-emerald-700'],
    ];

    const PERIOD_TYPES = [
        'annual'    => 'Anual',
        'quarterly' => 'Trimestral',
        'monthly'   => 'Mensual',
    ];

    const DRIVERS = [
        'ipc'                  => 'IPC proyectado',
        'inflation'            => 'Inflación',
        'smmlv'                => 'Incremento SMMLV',
        'sales_growth'         => 'Meta crecimiento ventas',
        'payroll_growth'       => 'Incremento nómina',
        'rent_growth'          => 'Incremento arrendamiento',
        'utilities_growth'     => 'Incremento serv. públicos',
        'purchases_growth'     => 'Incremento compras',
        'interest_rate'        => 'Tasa de interés',
        'services_growth'      => 'Incremento tarifas servicios',
        'fixed'                => 'Valor fijo (sin variación)',
        'manual'               => 'Manual (ingreso directo)',
        'custom_pct'           => 'Porcentaje personalizado',
    ];

    public function getPeriodLabels(): array
    {
        $labels = [];
        for ($i = 0; $i <= $this->periods_count; $i++) {
            $labels[$i] = $this->buildPeriodLabel($i);
        }
        return $labels;
    }

    public function buildPeriodLabel(int $index): string
    {
        $year = $this->base_year + match ($this->period_type) {
            'annual'    => $index,
            'quarterly' => intdiv($index, 4),
            'monthly'   => intdiv($index, 12),
        };

        return match ($this->period_type) {
            'annual'    => (string) $year,
            'quarterly' => "T" . (($index % 4) + 1) . " {$year}",
            'monthly'   => \Carbon\Carbon::createFromDate($this->base_year, 1, 1)
                               ->addMonths($index)->translatedFormat('M Y'),
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

    public function sections(): HasMany
    {
        return $this->hasMany(BudgetSection::class)->orderBy('sort_order');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class)->orderBy('sort_order');
    }

    public function values(): HasMany
    {
        return $this->hasMany(BudgetValue::class);
    }
}
