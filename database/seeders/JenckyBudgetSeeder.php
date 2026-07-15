<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\BudgetClientVariable;
use App\Models\BudgetLine;
use App\Models\BudgetSection;
use App\Models\BudgetValue;
use Illuminate\Database\Seeder;

class JenckyBudgetSeeder extends Seeder
{
    // client_id => user_id
    private array $clients = [5 => 4, 10 => 5];

    public function run(): void
    {
        foreach ($this->clients as $clientId => $userId) {
            $this->seedVariables($userId, $clientId);
            $this->seedVentas($userId, $clientId);
            $this->seedGastos($userId, $clientId);
            $this->seedFlujoCaja($userId, $clientId);
            $this->seedNomina($userId, $clientId);
            $this->seedCompras($userId, $clientId);
        }
    }

    // ── Variables macroeconómicas ────────────────────────────────────────────

    private function seedVariables(int $userId, int $clientId): void
    {
        BudgetClientVariable::updateOrCreate(
            ['user_id' => $userId, 'client_id' => $clientId],
            [
                'ipc'                  => 6.77,
                'inflation'            => 5.00,
                'smmlv_increase'       => 9.54,
                'sales_growth'         => 12.00,
                'sales_growth_monthly' => 0.95,
                'new_clients_pct'      => 8.00,
                'payroll_growth'       => 9.54,
                'rent_growth'          => 6.77,
                'utilities_growth'     => 8.50,
                'purchases_growth'     => 6.50,
                'interest_rate'        => 13.50,
                'services_growth'      => 8.00,
            ]
        );
    }

    // ── Presupuesto de Ventas ────────────────────────────────────────────────

    private function seedVentas(int $userId, int $clientId): void
    {
        $budget = Budget::create([
            'user_id'       => $userId,
            'client_id'     => $clientId,
            'name'          => 'Presupuesto de Ventas 2025–2028',
            'type'          => 'ventas',
            'base_year'     => 2025,
            'period_type'   => 'annual',
            'periods_count' => 3,
            'status'        => 'projected',
            'notes'         => 'Proyección basada en meta de crecimiento 12% anual. IPC referencia 6.77%. Incluye incorporación de 3 nuevos clientes anuales estimados.',
        ]);

        $sections = [
            [
                'name'  => 'Ingresos Operacionales',
                'lines' => [
                    ['name' => 'Servicios contables y de revisión',   'driver' => 'sales_growth',    'base' => 36_000_000],
                    ['name' => 'Declaraciones tributarias',            'driver' => 'sales_growth',    'base' => 18_000_000],
                    ['name' => 'Consultoría tributaria y fiscal',      'driver' => 'sales_growth',    'base' => 24_000_000],
                    ['name' => 'Revisoría fiscal',                     'driver' => 'sales_growth',    'base' => 12_000_000],
                    ['name' => 'Outsourcing nómina y RRHH',            'driver' => 'sales_growth',    'base' =>  8_400_000],
                    ['name' => 'Auditoría de estados financieros',     'driver' => 'sales_growth',    'base' =>  6_000_000],
                    ['name' => 'Legalización de empresas',             'driver' => 'sales_growth',    'base' =>  4_800_000],
                    ['name' => 'Otros servicios profesionales',        'driver' => 'sales_growth',    'base' =>  3_600_000],
                ],
            ],
            [
                'name'  => 'Ingresos por Nuevos Clientes',
                'lines' => [
                    ['name' => 'Nuevos clientes proyectados (unid.)', 'driver' => 'sales_growth',    'base' =>  5_000_000],
                    ['name' => 'Ingreso promedio por nuevo cliente',  'driver' => 'services_growth', 'base' =>  2_500_000],
                ],
            ],
            [
                'name'  => 'Ingresos No Operacionales',
                'lines' => [
                    ['name' => 'Intereses ganados',                   'driver' => 'interest_rate',   'base' =>    800_000],
                    ['name' => 'Descuentos financieros recibidos',    'driver' => 'ipc',             'base' =>    300_000],
                    ['name' => 'Otros ingresos no operacionales',     'driver' => 'ipc',             'base' =>    500_000],
                ],
            ],
        ];

        $this->insertSections($budget, $sections, [12.00, 12.00, 6.77, 6.77, 8.00, 13.50]);
    }

    // ── Presupuesto de Gastos ────────────────────────────────────────────────

    private function seedGastos(int $userId, int $clientId): void
    {
        $budget = Budget::create([
            'user_id'       => $userId,
            'client_id'     => $clientId,
            'name'          => 'Presupuesto de Gastos 2025–2028',
            'type'          => 'gastos',
            'base_year'     => 2025,
            'period_type'   => 'annual',
            'periods_count' => 3,
            'status'        => 'projected',
            'notes'         => 'Gastos proyectados con incremento SMMLV 9.54% para nómina y prestaciones. Arrendamiento proyectado con IPC 6.77% (límite legal). Servicios públicos con incremento estimado 8.5%.',
        ]);

        $sections = [
            [
                'name'  => 'Gastos de Personal',
                'lines' => [
                    ['name' => 'Salarios básicos',                   'driver' => 'smmlv',           'base' => 54_000_000],
                    ['name' => 'Horas extras y recargos',            'driver' => 'smmlv',           'base' =>  2_160_000],
                    ['name' => 'Bonificaciones y comisiones',        'driver' => 'payroll_growth',  'base' =>  3_600_000],
                    ['name' => 'Auxilio de transporte',              'driver' => 'smmlv',           'base' =>  3_060_000],
                    ['name' => 'Salud empleador (8.5%)',             'driver' => 'smmlv',           'base' =>  4_590_000],
                    ['name' => 'Pensión empleador (12%)',            'driver' => 'smmlv',           'base' =>  6_480_000],
                    ['name' => 'ARL',                                'driver' => 'smmlv',           'base' =>    648_000],
                    ['name' => 'Caja de compensación (4%)',          'driver' => 'smmlv',           'base' =>  2_160_000],
                    ['name' => 'SENA (2%)',                          'driver' => 'smmlv',           'base' =>  1_080_000],
                    ['name' => 'ICBF (3%)',                          'driver' => 'smmlv',           'base' =>  1_620_000],
                    ['name' => 'Cesantías (8.33%)',                  'driver' => 'smmlv',           'base' =>  4_498_200],
                    ['name' => 'Intereses sobre cesantías (1%)',     'driver' => 'smmlv',           'base' =>    449_820],
                    ['name' => 'Prima de servicios (8.33%)',         'driver' => 'smmlv',           'base' =>  4_498_200],
                    ['name' => 'Vacaciones (4.17%)',                 'driver' => 'smmlv',           'base' =>  2_251_800],
                ],
            ],
            [
                'name'  => 'Gastos Generales de Operación',
                'lines' => [
                    ['name' => 'Arrendamiento oficina',              'driver' => 'rent_growth',     'base' =>  4_800_000],
                    ['name' => 'Servicio de energía eléctrica',      'driver' => 'utilities_growth','base' =>  1_800_000],
                    ['name' => 'Agua y acueducto',                   'driver' => 'utilities_growth','base' =>    720_000],
                    ['name' => 'Internet y telefonía',               'driver' => 'utilities_growth','base' =>  1_800_000],
                    ['name' => 'Gas domiciliario',                   'driver' => 'utilities_growth','base' =>    480_000],
                    ['name' => 'Papelería y útiles de oficina',      'driver' => 'ipc',             'base' =>    900_000],
                    ['name' => 'Cafetería y aseo',                   'driver' => 'ipc',             'base' =>    720_000],
                    ['name' => 'Aseo y limpieza',                    'driver' => 'ipc',             'base' =>    480_000],
                    ['name' => 'Transporte y viáticos',              'driver' => 'ipc',             'base' =>  2_400_000],
                    ['name' => 'Publicidad y mercadeo',              'driver' => 'sales_growth',    'base' =>  1_800_000],
                ],
            ],
            [
                'name'  => 'Gastos de Tecnología',
                'lines' => [
                    ['name' => 'Software contable y licencias',      'driver' => 'ipc',             'base' =>  3_600_000],
                    ['name' => 'Mantenimiento equipos de cómputo',   'driver' => 'ipc',             'base' =>    900_000],
                    ['name' => 'Hosting y servicios en la nube',     'driver' => 'ipc',             'base' =>    720_000],
                    ['name' => 'Otros gastos de tecnología',         'driver' => 'ipc',             'base' =>    360_000],
                ],
            ],
            [
                'name'  => 'Gastos Financieros',
                'lines' => [
                    ['name' => 'Comisiones bancarias',               'driver' => 'ipc',             'base' =>    720_000],
                    ['name' => 'Intereses sobre créditos',           'driver' => 'interest_rate',   'base' =>  1_800_000],
                    ['name' => 'GMF – 4x1000',                       'driver' => 'sales_growth',    'base' =>    900_000],
                ],
            ],
            [
                'name'  => 'Otros Gastos',
                'lines' => [
                    ['name' => 'Seguros',                            'driver' => 'ipc',             'base' =>  1_800_000],
                    ['name' => 'Honorarios profesionales externos',  'driver' => 'services_growth', 'base' =>  3_600_000],
                    ['name' => 'Capacitación y actualización',       'driver' => 'ipc',             'base' =>  2_400_000],
                    ['name' => 'Gastos legales y notariales',        'driver' => 'ipc',             'base' =>    900_000],
                    ['name' => 'Gastos diversos',                    'driver' => 'ipc',             'base' =>  1_800_000],
                ],
            ],
        ];

        $this->insertSections($budget, $sections, [9.54, 6.77, 8.50, 12.00, 13.50, 8.00]);
    }

    // ── Flujo de Caja ────────────────────────────────────────────────────────

    private function seedFlujoCaja(int $userId, int $clientId): void
    {
        $budget = Budget::create([
            'user_id'       => $userId,
            'client_id'     => $clientId,
            'name'          => 'Flujo de Caja Proyectado 2025–2028',
            'type'          => 'flujo_caja',
            'base_year'     => 2025,
            'period_type'   => 'annual',
            'periods_count' => 3,
            'status'        => 'projected',
            'notes'         => 'Flujo de caja elaborado con base en política de cartera 45 días. Se proyecta reducción de cartera vencida del 15% anual. Incluye cuota crédito vehículo $2.4M/mes.',
        ]);

        $sections = [
            [
                'name'  => 'Saldo Inicial',
                'lines' => [
                    ['name' => 'Saldo inicial de caja y bancos',      'driver' => 'fixed',           'base' =>  8_000_000],
                ],
            ],
            [
                'name'  => 'Ingresos de Efectivo',
                'lines' => [
                    ['name' => 'Cobros a clientes (cartera)',          'driver' => 'sales_growth',    'base' => 105_000_000],
                    ['name' => 'Ventas de contado',                    'driver' => 'sales_growth',    'base' =>   8_500_000],
                    ['name' => 'Préstamos bancarios recibidos',        'driver' => 'fixed',           'base' =>  10_000_000],
                    ['name' => 'Aportes de socios / capital',          'driver' => 'fixed',           'base' =>   5_000_000],
                    ['name' => 'Rendimientos financieros',             'driver' => 'interest_rate',   'base' =>     800_000],
                    ['name' => 'Otros ingresos de efectivo',           'driver' => 'ipc',             'base' =>     500_000],
                ],
            ],
            [
                'name'  => 'Egresos Operativos',
                'lines' => [
                    ['name' => 'Pago a proveedores',                   'driver' => 'purchases_growth','base' =>  15_000_000],
                    ['name' => 'Pago de nómina',                       'driver' => 'smmlv',           'base' =>  54_000_000],
                    ['name' => 'Pago de prestaciones sociales',        'driver' => 'smmlv',           'base' =>  11_700_000],
                    ['name' => 'Pago parafiscales y seguridad social', 'driver' => 'smmlv',           'base' =>  16_578_000],
                    ['name' => 'Pago de arrendamientos',               'driver' => 'rent_growth',     'base' =>   4_800_000],
                    ['name' => 'Pago servicios públicos',              'driver' => 'utilities_growth','base' =>   4_800_000],
                    ['name' => 'Papelería y gastos menores',           'driver' => 'ipc',             'base' =>   1_500_000],
                ],
            ],
            [
                'name'  => 'Egresos Tributarios',
                'lines' => [
                    ['name' => 'Pago IVA',                             'driver' => 'sales_growth',    'base' =>   5_000_000],
                    ['name' => 'Pago retefuente',                      'driver' => 'sales_growth',    'base' =>   3_500_000],
                    ['name' => 'Pago renta / CREE',                    'driver' => 'sales_growth',    'base' =>   4_200_000],
                    ['name' => 'Pago ICA',                             'driver' => 'sales_growth',    'base' =>     840_000],
                    ['name' => 'Otros impuestos y contribuciones',     'driver' => 'ipc',             'base' =>     500_000],
                ],
            ],
            [
                'name'  => 'Egresos Financieros',
                'lines' => [
                    ['name' => 'Pago cuotas de crédito (capital)',     'driver' => 'fixed',           'base' =>   2_400_000],
                    ['name' => 'Pago intereses de créditos',           'driver' => 'interest_rate',   'base' =>   1_800_000],
                    ['name' => 'GMF y comisiones bancarias',           'driver' => 'sales_growth',    'base' =>     900_000],
                ],
            ],
            [
                'name'  => 'Saldo Final',
                'lines' => [
                    ['name' => 'Saldo final de caja y bancos',         'driver' => 'fixed',           'base' =>   5_280_000],
                ],
            ],
        ];

        $this->insertSections($budget, $sections, [12.00, 9.54, 6.77, 8.50, 13.50, 6.50]);
    }

    // ── Presupuesto de Nómina ────────────────────────────────────────────────

    private function seedNomina(int $userId, int $clientId): void
    {
        $budget = Budget::create([
            'user_id'       => $userId,
            'client_id'     => $clientId,
            'name'          => 'Presupuesto de Nómina 2025–2028',
            'type'          => 'nomina',
            'base_year'     => 2025,
            'period_type'   => 'annual',
            'periods_count' => 3,
            'status'        => 'projected',
            'notes'         => 'SMMLV 2025: $1,423,500. Nómina 3 empleados + propietaria. Incremento proyectado 9.54% anual (equivalente a ajuste SMMLV 2025). Costo total empleador incluye factor prestacional 52%.',
        ]);

        $sections = [
            [
                'name'  => 'Salarios y Compensaciones',
                'lines' => [
                    ['name' => 'Gerente / Directora',                 'driver' => 'smmlv',           'base' => 54_000_000],
                    ['name' => 'Contador principal',                  'driver' => 'smmlv',           'base' => 36_000_000],
                    ['name' => 'Auxiliar contable 1',                 'driver' => 'smmlv',           'base' => 21_600_000],
                    ['name' => 'Asistente administrativo',            'driver' => 'smmlv',           'base' => 18_000_000],
                    ['name' => 'Otros cargos',                        'driver' => 'smmlv',           'base' =>          0],
                    ['name' => 'Horas extras y recargos',             'driver' => 'smmlv',           'base' =>  2_160_000],
                    ['name' => 'Bonificaciones',                      'driver' => 'payroll_growth',  'base' =>  3_600_000],
                    ['name' => 'Auxilio de transporte',               'driver' => 'smmlv',           'base' =>  3_060_000],
                ],
            ],
            [
                'name'  => 'Aportes Parafiscales Empleador',
                'lines' => [
                    ['name' => 'Salud empleador (8.5%)',              'driver' => 'smmlv',           'base' =>  9_180_000],
                    ['name' => 'Pensión empleador (12%)',             'driver' => 'smmlv',           'base' => 12_960_000],
                    ['name' => 'ARL',                                 'driver' => 'smmlv',           'base' =>  1_296_000],
                    ['name' => 'Caja de compensación familiar (4%)', 'driver' => 'smmlv',           'base' =>  4_320_000],
                    ['name' => 'SENA (2%)',                           'driver' => 'smmlv',           'base' =>  2_160_000],
                    ['name' => 'ICBF (3%)',                           'driver' => 'smmlv',           'base' =>  3_240_000],
                ],
            ],
            [
                'name'  => 'Prestaciones Sociales',
                'lines' => [
                    ['name' => 'Cesantías (8.33%)',                   'driver' => 'smmlv',           'base' =>  8_996_400],
                    ['name' => 'Intereses sobre cesantías (1%)',      'driver' => 'smmlv',           'base' =>    899_640],
                    ['name' => 'Prima de servicios (8.33%)',          'driver' => 'smmlv',           'base' =>  8_996_400],
                    ['name' => 'Vacaciones (4.17%)',                  'driver' => 'smmlv',           'base' =>  4_503_600],
                ],
            ],
            [
                'name'  => 'Deducciones Empleado',
                'lines' => [
                    ['name' => 'Salud empleado (4%)',                 'driver' => 'smmlv',           'base' =>  4_320_000],
                    ['name' => 'Pensión empleado (4%)',               'driver' => 'smmlv',           'base' =>  4_320_000],
                    ['name' => 'Retención en la fuente salarios',     'driver' => 'smmlv',           'base' =>  1_800_000],
                    ['name' => 'Libranzas y descuentos varios',       'driver' => 'fixed',           'base' =>          0],
                ],
            ],
            [
                'name'  => 'Costo Total de Nómina',
                'lines' => [
                    ['name' => 'Costo total empleador (factor ×1.52)','driver' => 'smmlv',           'base' => 196_972_040],
                    ['name' => 'Neto pagado a empleados',             'driver' => 'smmlv',           'base' => 124_320_000],
                ],
            ],
        ];

        $this->insertSections($budget, $sections, [9.54, 9.54, 9.54, 9.54, 9.54]);
    }

    // ── Presupuesto de Compras ───────────────────────────────────────────────

    private function seedCompras(int $userId, int $clientId): void
    {
        $budget = Budget::create([
            'user_id'       => $userId,
            'client_id'     => $clientId,
            'name'          => 'Presupuesto de Compras 2025–2028',
            'type'          => 'compras',
            'base_year'     => 2025,
            'period_type'   => 'annual',
            'periods_count' => 3,
            'status'        => 'projected',
            'notes'         => 'Compras proyectadas con incremento del 6.5% anual (inflación de insumos + proveedor). Se mantiene política de rotación de inventario de 30 días.',
        ]);

        $sections = [
            [
                'name'  => 'Compras de Mercancías / Insumos',
                'lines' => [
                    ['name' => 'Compras brutas de mercancías',        'driver' => 'purchases_growth','base' => 18_000_000],
                    ['name' => 'Compras de materias primas',          'driver' => 'purchases_growth','base' =>  6_000_000],
                    ['name' => 'Compras de materiales de empaque',    'driver' => 'purchases_growth','base' =>  1_200_000],
                    ['name' => 'Devoluciones en compras',             'driver' => 'purchases_growth','base' =>    900_000],
                    ['name' => 'Descuentos comerciales recibidos',    'driver' => 'ipc',             'base' =>    600_000],
                ],
            ],
            [
                'name'  => 'Costos de Importación',
                'lines' => [
                    ['name' => 'Fletes y transporte',                 'driver' => 'ipc',             'base' =>  1_200_000],
                    ['name' => 'Seguros sobre mercancías',            'driver' => 'ipc',             'base' =>    360_000],
                    ['name' => 'Derechos de aduana e IVA importado', 'driver' => 'ipc',             'base' =>    480_000],
                    ['name' => 'Gastos de agenciamiento aduanero',   'driver' => 'ipc',             'base' =>    240_000],
                ],
            ],
            [
                'name'  => 'Control de Inventarios',
                'lines' => [
                    ['name' => 'Inventario inicial del período',      'driver' => 'purchases_growth','base' =>  3_500_000],
                    ['name' => 'Inventario final del período',        'driver' => 'purchases_growth','base' =>  3_500_000],
                    ['name' => 'Costo de ventas (calculado)',         'driver' => 'purchases_growth','base' => 21_300_000],
                ],
            ],
            [
                'name'  => 'Proveedores Principales',
                'lines' => [
                    ['name' => 'Proveedor principal – Papelería Ltda.','driver' => 'purchases_growth','base' =>  7_200_000],
                    ['name' => 'Proveedor 2 – Suministros Tech S.A.', 'driver' => 'purchases_growth','base' =>  4_800_000],
                    ['name' => 'Proveedor 3 – Servicios Logísticos',  'driver' => 'purchases_growth','base' =>  3_600_000],
                    ['name' => 'Otros proveedores menores',            'driver' => 'purchases_growth','base' =>  2_400_000],
                ],
            ],
        ];

        $this->insertSections($budget, $sections, [6.50, 6.77, 6.50, 6.50]);
    }

    // ── Helper genérico ──────────────────────────────────────────────────────

    private function insertSections(Budget $budget, array $sections, array $ratesHint): void
    {
        $vars = BudgetClientVariable::where('user_id', $budget->user_id)
                    ->where('client_id', $budget->client_id)
                    ->first();

        foreach ($sections as $sIdx => $sData) {
            $section = BudgetSection::create([
                'budget_id'  => $budget->id,
                'name'       => $sData['name'],
                'sort_order' => $sIdx,
            ]);

            foreach ($sData['lines'] as $lIdx => $lData) {
                $line = BudgetLine::create([
                    'budget_id'         => $budget->id,
                    'section_id'        => $section->id,
                    'name'              => $lData['name'],
                    'sort_order'        => $lIdx,
                    'projection_driver' => $lData['driver'],
                    'custom_rate'       => null,
                    'is_subtotal'       => false,
                    'sign_negative'     => false,
                ]);

                $base = (float) $lData['base'];

                for ($p = 0; $p <= $budget->periods_count; $p++) {
                    $value = $this->project($base, $lData['driver'], $p, $vars);

                    BudgetValue::create([
                        'budget_id'          => $budget->id,
                        'line_id'            => $line->id,
                        'period_label'       => $budget->buildPeriodLabel($p),
                        'period_index'       => $p,
                        'value'              => $value,
                        'is_manual_override' => ($p === 0),
                    ]);
                }
            }
        }
    }

    private function project(float $base, string $driver, int $n, ?BudgetClientVariable $vars): float
    {
        if ($n === 0 || $driver === 'manual' || $driver === 'fixed') {
            return $base;
        }

        $rate = match ($driver) {
            'ipc'                  => $vars?->ipc            ?? 6.77,
            'inflation'            => $vars?->inflation       ?? 5.00,
            'smmlv'                => $vars?->smmlv_increase  ?? 9.54,
            'sales_growth'         => $vars?->sales_growth    ?? 12.00,
            'sales_growth_monthly' => $vars?->sales_growth_monthly ?? 0.95,
            'new_clients_pct'      => $vars?->sales_growth    ?? 12.00,
            'payroll_growth'       => $vars?->payroll_growth  ?? 9.54,
            'rent_growth'          => $vars?->rent_growth     ?? 6.77,
            'utilities_growth'     => $vars?->utilities_growth ?? 8.50,
            'purchases_growth'     => $vars?->purchases_growth ?? 6.50,
            'interest_rate'        => $vars?->interest_rate   ?? 13.50,
            'services_growth'      => $vars?->services_growth ?? 8.00,
            default                => 0.0,
        };

        return round($base * pow(1 + $rate / 100, $n), 2);
    }
}
