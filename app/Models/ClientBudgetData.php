<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Datos" del cliente para presupuestos: políticas planas (Datos base del
 * caso práctico de Actualícese, filas 17-24). Los indicadores año a año
 * (inflación, SMMLV, auxilio de transporte) viven en ClientBudgetYearlyData.
 */
class ClientBudgetData extends Model
{
    protected $table = 'client_budget_data';

    protected $fillable = [
        'user_id', 'client_id',
        'credit_sales_pct', 'collection_days', 'supplier_payment_days',
        'interest_rate', 'income_tax_rate', 'legal_reserve_pct', 'partner_contributions',
        'ratio_liquidity_target', 'ratio_debt_target', 'ratio_interest_coverage_target',
        'ratio_roe_target', 'ratio_roa_target', 'ratio_working_capital_target',
    ];

    protected $casts = [
        'credit_sales_pct'       => 'float',
        'collection_days'        => 'integer',
        'supplier_payment_days'  => 'integer',
        'interest_rate'          => 'float',
        'income_tax_rate'        => 'float',
        'legal_reserve_pct'      => 'float',
        'partner_contributions'  => 'float',
        'ratio_liquidity_target'          => 'float',
        'ratio_debt_target'               => 'float',
        'ratio_interest_coverage_target'  => 'float',
        'ratio_roe_target'                => 'float',
        'ratio_roa_target'                => 'float',
        'ratio_working_capital_target'    => 'float',
    ];

    public static function labels(): array
    {
        return [
            'credit_sales_pct'      => '% Ventas a crédito',
            'collection_days'       => 'Política de cobro de cartera (días)',
            'supplier_payment_days' => 'Política de pago a proveedores (días)',
            'interest_rate'         => 'Costo de la obligación financiera (%)',
            'income_tax_rate'       => 'Tarifa de impuesto de renta (%)',
            'legal_reserve_pct'     => 'Reserva legal (%)',
            'partner_contributions' => 'Aportes de socios (sugerido)',
        ];
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
