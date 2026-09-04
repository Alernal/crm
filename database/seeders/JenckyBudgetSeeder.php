<?php

namespace Database\Seeders;

use App\Http\Controllers\BudgetController;
use App\Models\Budget;
use App\Models\BudgetValue;
use App\Models\ClientBudgetData;
use App\Models\ClientBudgetYearlyData;
use Illuminate\Database\Seeder;
use ReflectionMethod;

/**
 * Cliente demo "JENCKY CAROLINA TAPIA DORADO", cuya actividad económica es
 * la comercialización de equipos de tecnología. El submódulo Presupuestos
 * quedó reducido a un solo tipo autocontenido (Flujo de Caja, sin los
 * antiguos presupuestos separados de Ventas/Gastos ni el vínculo cruzado
 * entre ellos) — este seeder reemplaza cualquier presupuesto de Flujo de
 * Caja anterior de este cliente con cifras mensuales digitadas directamente,
 * usando los nuevos drivers por defecto (`BudgetController::defaultFlujoCaja()`)
 * en vez de heredar valores de otro presupuesto. Reutiliza el motor real de
 * proyección (`BudgetController`) en vez de reimplementar el cálculo aquí,
 * para no duplicar/desincronizar.
 */
class JenckyBudgetSeeder extends Seeder
{
    // client_id => user_id — solo el cliente demo de la cuenta de ejemplo
    // (contador@ejemplo.com). El client_id=10 de otra cuenta ya no se toca
    // aquí: divergió a datos reales editados a mano en la UI ("Local
    // Sincelejo", ESF/ERI) que este seeder destructivo no debe pisar.
    private array $clients = [5 => 4];

    public function run(): void
    {
        foreach ($this->clients as $clientId => $userId) {
            Budget::where('client_id', $clientId)->where('user_id', $userId)
                ->where('type', 'flujo_caja')->delete();

            $this->addFlujoCajaFor($clientId, $userId);
        }
    }

    /**
     * Agrega un presupuesto de Flujo de Caja de 12 meses completamente
     * diligenciado (Ppto + Real ene-ago) para un cliente, SIN borrar ningún
     * presupuesto existente de ese cliente — a diferencia de run(), pensado
     * para clientes con datos reales ya cargados a mano en la UI (ej.
     * client_id=10, que ya tiene "Local Sincelejo" + ESF/ERI).
     */
    public function addFlujoCajaFor(int $clientId, int $userId): void
    {
        $controller = new BudgetController();
        $persist = new ReflectionMethod($controller, 'persistStructure');
        $persist->setAccessible(true);
        $projectFlujoCaja = new ReflectionMethod($controller, 'projectFlujoCaja');
        $projectFlujoCaja->setAccessible(true);

        $data = $this->seedData($userId, $clientId);
        $this->seedFlujoCaja($userId, $clientId, $persist, $projectFlujoCaja, $data);
    }

    // ── Datos ────────────────────────────────────────────────────────────────

    private function seedData(int $userId, int $clientId): ClientBudgetData
    {
        $data = ClientBudgetData::updateOrCreate(
            ['user_id' => $userId, 'client_id' => $clientId],
            [
                'credit_sales_pct'      => 50.00,
                'collection_days'       => 30,
                'supplier_payment_days' => 45,
                'interest_rate'         => 22.00,
                'income_tax_rate'       => 35.00,
                'legal_reserve_pct'     => 10.00,
                'partner_contributions' => 30_000_000,
            ]
        );

        // Inflación/SMMLV reales publicados para 2026 (mismos valores que
        // PayrollLegalSettingSeeder), proyectados con criterio para 2027-2028.
        $years = [
            2026 => ['inflacion' => 4.30, 'smmlv' => 1_750_905, 'auxilio_transporte' => 249_095],
            2027 => ['inflacion' => 3.90, 'smmlv' => 1_821_690, 'auxilio_transporte' => 258_810],
            2028 => ['inflacion' => 3.70, 'smmlv' => 1_889_293, 'auxilio_transporte' => 268_386],
        ];

        foreach ($years as $year => $indicators) {
            foreach ($indicators as $indicator => $value) {
                ClientBudgetYearlyData::updateOrCreate(
                    ['client_id' => $clientId, 'indicator' => $indicator, 'year' => $year],
                    ['user_id' => $userId, 'value' => $value]
                );
            }
        }

        return $data->fresh();
    }

    // ── Flujo de Caja: autocontenido ─────────────────────────────────────────

    private function seedFlujoCaja(int $userId, int $clientId, ReflectionMethod $persist, ReflectionMethod $projectFlujoCaja, ClientBudgetData $data): void
    {
        $budget = Budget::create([
            'user_id'                          => $userId,
            'client_id'                        => $clientId,
            'name'                              => 'Flujo de Caja Proyectado 2026',
            'type'                              => 'flujo_caja',
            'base_year'                         => 2026,
            'period_type'                       => 'monthly',
            'periods_count'                     => 12,
            'status'                            => 'draft',
        ]);

        $controller = new BudgetController();
        $sections = $controller->defaultSectionsFor('flujo_caja');

        // Cifras mensuales de referencia (editables) para TODOS los rubros
        // del catálogo (comercializadora de equipos de tecnología) — a
        // diferencia de la versión anterior de este seeder, que dejaba en 0
        // cualquier rubro sin un valor "interesante" a propósito, aquí se
        // digita un valor base razonable en cada renglón para poder ver el
        // dashboard/gráfica con las 32 filas del catálogo pobladas.
        // "Recaudo de cartera" necesita un valor de período 0 distinto de 0
        // solo para activar su auto-cálculo (ver projectRecaudoCartera) —
        // el valor real que queda en pantalla lo deriva de "Ventas" +
        // Datos.credit_sales_pct/collection_days.
        $baseValues = [
            'Saldo inicial'                                        => 5_000_000,

            // Entradas
            'Ventas'                                                => 25_000_000,
            'Recaudo de cartera'                                    => 25_000_000,
            'Aportes de socios'                                     => 1_500_000,
            'Préstamos bancarios solicitados'                       => 1_000_000,
            'Préstamos solicitados a socios o accionistas'          => 500_000,
            'Recaudo de préstamos realizados a empleados'           => 200_000,
            'Ventas de propiedad, planta y equipo'                  => 150_000,
            'Venta de activos intangibles'                          => 80_000,
            'Intereses financieros recibidos'                       => 100_000,
            'Dividendos y participaciones recibidos'                => 80_000,
            'Devolución de impuestos'                               => 200_000,
            'Otras entradas'                                        => 250_000,

            // Salidas
            'Pago a proveedores'                                    => 30_000_000,
            'Pago de servicios públicos'                            => 1_200_000,
            'Pago de nómina'                                        => 10_000_000,
            'Pago por arrendamiento'                                => 3_000_000,
            'Pagos por publicidad'                                  => 800_000,
            'Pagos por asesorías'                                   => 600_000,
            'Pagos por reparaciones y mantenimiento'                => 400_000,
            'Pagos a otros acreedores'                              => 300_000,
            'Pagos por seguros'                                     => 700_000,
            'Pago de impuesto de renta'                             => 1_000_000,
            'Pago de IVA'                                           => 2_500_000,
            'Pago de impuesto al consumo'                           => 150_000,
            'Pago de retención en la fuente'                        => 500_000,
            'Pago de cuotas por préstamos bancarios'                => 1_200_000,
            'Pago de cuotas de préstamos solicitados a socios o accionistas' => 300_000,
            'Préstamos otorgados a empleados'                       => 200_000,
            'Pago de dividendos o participaciones a socios o accionistas'   => 400_000,
            'Compra de propiedad, planta y equipo'                  => 800_000,
            'Compra de intangibles'                                 => 200_000,
            'Intereses financieros pagados'                         => 450_000,
            'Otros pagos'                                           => 250_000,
        ];

        // "Ventas" crece con una tasa mensual moderada (0.8%/mes ≈ 10% anual)
        // — el resto de rubros con driver custom_pct/inflation/smmlv no
        // necesita `custom_rate` propio salvo Ventas.
        $customRates = ['Ventas' => 0.8];

        foreach ($sections as &$section) {
            foreach ($section['lines'] as &$line) {
                $line['projection_driver'] = $line['driver'];
                $line['custom_rate'] = $customRates[$line['name']] ?? null;
                $line['sign_negative'] = false;
                $line['base_value'] = $baseValues[$line['name']] ?? 0;
            }
        }
        unset($section, $line);

        $persist->invoke($controller, $budget, $sections);

        $budget->load(['client', 'sections.lines.values']);
        $projectFlujoCaja->invoke($controller, $budget, $data);

        $this->seedReal($controller, $budget);
    }

    /**
     * Cifras "Real" para los meses ya transcurridos del año en curso (hoy:
     * ago-2026). `period_index=0` es la propia columna de enero (no una fila
     * de base oculta, a diferencia de ESF/ERI) así que los 8 meses ya
     * vividos son period_index 0..7 (ene-ago) — con una variación
     * pseudo-aleatoria pero determinística (±12%) respecto al Ppto por
     * línea/período, para que "% Cumplimiento"/"Mayores variaciones" del
     * dashboard muestren algo real en vez de 0% en todas partes. Los meses
     * futuros (sep-2026 en adelante, period_index 8+) se quedan sin Real,
     * como corresponde a un presupuesto que todavía no se ha vivido.
     */
    private function seedReal(BudgetController $controller, Budget $budget): void
    {
        $recompute = new ReflectionMethod($controller, 'recomputeFlujoCajaBalances');
        $recompute->setAccessible(true);

        $budget->load(['sections.lines.values']);
        $sections = $budget->sections;
        $saldoInicialLine = $sections->first()->lines->first();
        $saldoFinalLine   = $sections->last()->lines->first();
        $lastRealPeriod = 7; // period_index 0..7 = ene-ago 2026

        BudgetValue::updateOrCreate(
            ['line_id' => $saldoInicialLine->id, 'period_index' => 0, 'value_type' => 'actual'],
            [
                'budget_id'          => $budget->id,
                'period_label'       => $budget->buildPeriodLabel(0),
                'value'              => $saldoInicialLine->getValueForPeriod(0, 'budgeted'),
                'is_manual_override' => true,
            ]
        );

        foreach ($sections->slice(1, $sections->count() - 2) as $section) {
            foreach ($section->lines as $line) {
                for ($i = 0; $i <= $lastRealPeriod; $i++) {
                    $ppto = $line->getValueForPeriod($i, 'budgeted');
                    if ($ppto == 0.0) continue;

                    $seed = crc32($line->name.'|'.$i) % 25; // 0..24 → -12%..+12%
                    $variance = ($seed - 12) / 100;
                    $real = round($ppto * (1 + $variance), 2);

                    BudgetValue::updateOrCreate(
                        ['line_id' => $line->id, 'period_index' => $i, 'value_type' => 'actual'],
                        [
                            'budget_id'          => $budget->id,
                            'period_label'       => $budget->buildPeriodLabel($i),
                            'value'              => $real,
                            'is_manual_override' => true,
                        ]
                    );
                }
            }
        }

        $budget->load(['sections.lines.values']);
        $recompute->invoke($controller, $budget, 'actual');

        // recomputeFlujoCajaBalances() siempre recorre 1..periods_count —
        // sin cifras Real digitadas, los períodos futuros arrastrarían un
        // saldo "Real" plano (net flow = 0) que nunca existió. Se borran los
        // saldos Real de los períodos aún no transcurridos.
        BudgetValue::whereIn('line_id', [$saldoInicialLine->id, $saldoFinalLine->id])
            ->where('value_type', 'actual')
            ->where('period_index', '>', $lastRealPeriod)
            ->delete();
    }
}
