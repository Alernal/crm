<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = [
        'user_id', 'client_id', 'name', 'type', 'sales_mode', 'arl_risk_level',
        'linked_sales_budget_id', 'linked_payroll_budget_id', 'linked_counterpart_budget_id',
        'investment_initial_cash', 'investment_partner_contributions', 'investment_loan_term_years',
        'base_year', 'base_month', 'period_type', 'periods_count', 'period_years', 'period_labels',
        'cutoff_date', 'status', 'notes',
    ];

    protected $casts = [
        'base_year'                        => 'integer',
        'base_month'                       => 'integer',
        'periods_count'                    => 'integer',
        'period_years'                     => 'array',
        'period_labels'                    => 'array',
        'cutoff_date'                      => 'date',
        'investment_initial_cash'          => 'float',
        'investment_partner_contributions' => 'float',
        'investment_loan_term_years'       => 'integer',
    ];

    const TYPES = [
        'flujo_caja' => 'Presupuesto financiero',
        'esf'        => 'Estado de Situación Financiera (ESF)',
        'eri'        => 'Estado de Resultados (ERI)',
    ];

    /** Submenú "Presupuestos" — un solo tipo, autocontenido y editable. */
    const PRESUPUESTO_TYPES = ['flujo_caja'];

    /** Submenú "Estados Financieros" — cifras reales digitadas por período. */
    const ESTADO_FINANCIERO_TYPES = ['esf', 'eri'];

    const TYPE_COLORS = [
        'flujo_caja' => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',    'icon_bg' => 'from-blue-500 to-blue-700'],
        'esf'        => ['bg' => 'bg-indigo-100',  'text' => 'text-indigo-700',  'icon_bg' => 'from-indigo-500 to-indigo-700'],
        'eri'        => ['bg' => 'bg-teal-100',    'text' => 'text-teal-700',    'icon_bg' => 'from-teal-500 to-teal-700'],
    ];

    /**
     * Nombres canónicos de los renglones que conectan ESF↔ERI, usados tanto
     * por `defaultEsf()`/`defaultEri()` (para nombrarlos) como por
     * `BudgetController::projectStatements()` (para encontrarlos) — una sola
     * fuente de verdad para no desincronizar el nombre en ambos lados.
     */
    const ESF_UTILIDAD_LINE          = 'Utilidad (pérdida) del período';
    const ESF_RESULTADOS_ACUM_LINE   = 'Resultados acumulados de ejercicios anteriores';
    const ESF_DEPRECIACION_LINE      = 'Depreciación acumulada';
    const ERI_DEPRECIACION_GASTO_LINE = 'Depreciaciones y amortizaciones';
    const ERI_UTILIDAD_NETA_KEY       = 'utilidad_neta';
    const ERI_EBIT_KEY                = 'ebit';
    const ERI_GASTOS_FINANCIEROS_LINE = 'Gastos financieros (intereses)';

    const ESF_SECTION_ROLES = [
        'activo_corriente'    => 'Activo corriente',
        'activo_no_corriente' => 'Activo no corriente',
        'pasivo_corriente'    => 'Pasivo corriente',
        'pasivo_no_corriente' => 'Pasivo no corriente',
        'patrimonio'          => 'Patrimonio',
    ];

    const ERI_SECTION_ROLES = [
        'ingresos_operacionales'    => 'Ingresos operacionales',
        'costo_ventas'              => 'Costo de ventas',
        'gastos_administracion'     => 'Gastos de administración',
        'gastos_ventas'             => 'Gastos de ventas',
        'ingresos_no_operacionales' => 'Ingresos no operacionales',
        'gastos_no_operacionales'   => 'Gastos no operacionales',
        'impuestos'                 => 'Provisión para impuestos',
        'ori'                       => 'Otro resultado integral (ORI)',
    ];

    const STATUS_LABELS = [
        'draft' => ['label' => 'Borrador', 'class' => 'bg-gray-100 text-gray-600'],
        'final' => ['label' => 'Aprobado', 'class' => 'bg-emerald-100 text-emerald-700'],
    ];

    const PERIOD_TYPES = [
        'annual'       => 'Anual',
        'semiannual'   => 'Semestral',
        'four_monthly' => 'Cuatrimestral',
        'quarterly'    => 'Trimestral',
        'monthly'      => 'Mensual',
    ];

    /**
     * Palabra base para la etiqueta editable por defecto de cada período no
     * anual en Estados Financieros ("Mes 1", "Cuatrimestre 2"...).
     */
    const PERIOD_LABEL_WORDS = [
        'semiannual'   => 'Semestre',
        'four_monthly' => 'Cuatrimestre',
        'quarterly'    => 'Trimestre',
        'monthly'      => 'Mes',
    ];

    const DRIVERS = [
        'inflation'  => 'Inflación (Datos)',
        'smmlv'      => 'SMMLV (Datos)',
        'fixed'      => 'Valor fijo (sin variación)',
        'manual'     => 'Manual (ingreso directo)',
        'custom_pct' => 'Porcentaje personalizado',
    ];

    public function getPeriodLabels(): array
    {
        $labels = [];
        for ($i = 0; $i <= $this->periods_count; $i++) {
            $labels[$i] = $this->buildPeriodLabel($i);
        }
        return $labels;
    }

    /**
     * Para Ventas/Gastos/Flujo de Caja el año se deriva siempre de `base_year`
     * (proyección hacia adelante). Para Estados Financieros (ESF/ERI) el
     * contador digita el año real de cada período — no necesariamente
     * consecutivo — en `period_years`, que tiene prioridad si está presente.
     */
    public function calendarYearForPeriod(int $index): int
    {
        if (is_array($this->period_years) && array_key_exists($index, $this->period_years) && $this->period_years[$index] !== null) {
            return (int) $this->period_years[$index];
        }

        return $this->base_year + match ($this->period_type) {
            'annual'       => $index,
            'semiannual'   => intdiv($index, 2),
            'four_monthly' => intdiv($index, 3),
            'quarterly'    => intdiv($index, 4),
            'monthly'      => intdiv(($this->base_month ?: 1) - 1 + $index, 12),
        };
    }

    public function dateForPeriod(int $index): \Carbon\Carbon
    {
        return match ($this->period_type) {
            'annual'       => \Carbon\Carbon::createFromDate($this->calendarYearForPeriod($index), 1, 1),
            'semiannual'   => \Carbon\Carbon::createFromDate($this->base_year, 1, 1)->addMonths($index * 6),
            'four_monthly' => \Carbon\Carbon::createFromDate($this->base_year, 1, 1)->addMonths($index * 4),
            'quarterly'    => \Carbon\Carbon::createFromDate($this->base_year, 1, 1)->addMonths($index * 3),
            // Mensual respeta el mes de inicio elegido (`base_month`) en vez
            // de asumir siempre enero — el resto de periodicidades no
            // anuales siguen ancladas a enero (no tienen selector propio).
            'monthly'      => \Carbon\Carbon::createFromDate($this->base_year, $this->base_month ?: 1, 1)->addMonths($index),
        };
    }

    /**
     * Fecha de corte (último día) de un período — para el encabezado "A corte"
     * de los Estados Financieros. A diferencia de `dateForPeriod()` (que da el
     * primer día y no respeta `period_years` en trimestral/mensual), usa
     * `calendarYearForPeriod()` para que los años digitados manualmente en
     * ESF/ERI se reflejen correctamente.
     */
    public function periodEndDate(int $index): \Carbon\Carbon
    {
        $year = $this->calendarYearForPeriod($index);

        return match ($this->period_type) {
            'semiannual'   => \Carbon\Carbon::createFromDate($year, 1, 1)->addMonths((($index % 2) + 1) * 6)->subDay(),
            'four_monthly' => \Carbon\Carbon::createFromDate($year, 1, 1)->addMonths((($index % 3) + 1) * 4)->subDay(),
            'quarterly'    => \Carbon\Carbon::createFromDate($year, 1, 1)->addMonths((($index % 4) + 1) * 3)->subDay(),
            'monthly'      => \Carbon\Carbon::createFromDate($this->base_year, $this->base_month ?: 1, 1)->addMonths($index + 1)->subDay(),
            default        => \Carbon\Carbon::createFromDate($year, 12, 31),
        };
    }

    /**
     * Tope de "N°" (períodos adicionales al período base) para que un
     * presupuesto con periodicidad distinta a anual nunca cruce de un año
     * calendario a otro. Mensual respeta el mes de inicio elegido;
     * trimestral/semestral/cuatrimestral siempre inician en enero (sin
     * selector de mes propio), así que su tope es fijo. Anual no tiene
     * límite de un año — `null` indica "sin tope específico".
     */
    public static function maxPeriodsCountFor(string $periodType, ?int $baseMonth = null): ?int
    {
        if ($periodType === 'monthly') {
            return max(0, 12 - ($baseMonth ?: 1));
        }

        $perYear = ['four_monthly' => 3, 'quarterly' => 4, 'semiannual' => 2][$periodType] ?? null;

        return $perYear ? $perYear - 1 : null;
    }

    /**
     * El año ya se muestra una sola vez en la cabecera del reporte ("Año
     * base" / metadatos del período) — repetirlo en cada columna T1/T2/Ene/
     * Feb… es redundante cuando todo el presupuesto cae en el mismo año.
     * Solo se agrega el año a la etiqueta cuando cambia respecto al período
     * anterior (o es el primero), para no perder la referencia en
     * presupuestos trimestrales/mensuales que cruzan de un año a otro.
     */
    public function buildPeriodLabel(int $index): string
    {
        // Estados Financieros con periodicidad distinta a anual: etiqueta de
        // texto libre editable ("Mes 1", "Cuatrimestre 2"...) en vez del
        // nombre calculado — nunca se usa para anual, donde cada período ES
        // un año y el número siempre debe reflejar el año real.
        if ($this->period_type !== 'annual' && is_array($this->period_labels) && !empty($this->period_labels[$index] ?? null)) {
            return $this->period_labels[$index];
        }

        $year = $this->calendarYearForPeriod($index);
        $yearChanged = $index === 0 || $year !== $this->calendarYearForPeriod($index - 1);

        return match ($this->period_type) {
            'annual'       => (string) $year,
            'semiannual'   => "S" . (($index % 2) + 1) . ($yearChanged ? " {$year}" : ''),
            'four_monthly' => "C" . (($index % 3) + 1) . ($yearChanged ? " {$year}" : ''),
            'quarterly'    => "T" . (($index % 4) + 1) . ($yearChanged ? " {$year}" : ''),
            'monthly'      => $this->dateForPeriod($index)->locale('es')->isoFormat($yearChanged ? 'MMM YYYY' : 'MMM'),
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

    public function investmentAssets(): HasMany
    {
        return $this->hasMany(BudgetInvestmentAsset::class);
    }

    public function linkedCounterpart(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'linked_counterpart_budget_id');
    }

    /**
     * Reporte "por productos/unidades" del presupuesto de Ventas: 3 filas por
     * renglón (Unidades / Precio unitario / Valor total). El precio del
     * período 0 sale del valor base cargado (value/quantity); los siguientes
     * períodos se indexan a la cadena de inflación por año calendario cargada
     * en "Datos" (ClientBudgetYearlyData), igual que `Ppto Ventas` del caso
     * práctico de Actualícese. Solo aplica si `sales_mode === 'unidades'`.
     */
    /**
     * Estructura Ppto/Real por rubro y período para un presupuesto de flujo de
     * caja (Saldo inicial → Entradas → Salidas → Saldo final), más los datos
     * para el gráfico "Disponible al final del período" Ppto vs. Real.
     * Requiere `sections.lines.values` precargado. Devuelve null si el
     * presupuesto no tiene la forma esperada (mínimo 2 secciones distintas).
     * Compartido entre la vista en pantalla, el PDF y la impresión.
     */
    public function buildCashFlowReport(): ?array
    {
        $sections = $this->sections;

        $valid = $sections->count() >= 2
            && optional($sections->first()->lines->first())->id
            && optional($sections->last()->lines->first())->id
            && $sections->first()->lines->first()->id !== $sections->last()->lines->first()->id;

        if (!$valid) {
            return null;
        }

        $saldoInicialLine = $sections->first()->lines->first();
        $saldoFinalLine   = $sections->last()->lines->first();
        $middleSections   = $sections->slice(1, $sections->count() - 2)->values();
        $entradasSections = $middleSections->where('is_outflow', false);
        $salidasSections  = $middleSections->where('is_outflow', true);
        $periodLabels     = $this->getPeriodLabels();
        $periods          = array_keys($periodLabels);

        $sumSections = function ($secs, int $idx, string $valueType) {
            $sum = 0.0;
            foreach ($secs as $sec) {
                foreach ($sec->lines as $ln) {
                    $sum += $ln->getValueForPeriod($idx, $valueType);
                }
            }
            return $sum;
        };

        $lineValues = fn (BudgetLine $line) => collect($periods)->mapWithKeys(fn ($idx) => [$idx => [
            'ppto' => $line->getValueForPeriod($idx, 'budgeted'),
            'real' => $line->getValueForPeriod($idx, 'actual'),
        ]])->all();

        $totalEntradas = ['budgeted' => [], 'actual' => []];
        $totalSalidas  = ['budgeted' => [], 'actual' => []];
        foreach ($periods as $idx) {
            $totalEntradas['budgeted'][$idx] = $sumSections($entradasSections, $idx, 'budgeted');
            $totalEntradas['actual'][$idx]   = $sumSections($entradasSections, $idx, 'actual');
            $totalSalidas['budgeted'][$idx]  = $sumSections($salidasSections, $idx, 'budgeted');
            $totalSalidas['actual'][$idx]    = $sumSections($salidasSections, $idx, 'actual');
        }

        $rows = [];

        $rows[] = [
            'type' => 'final', 'label' => $saldoInicialLine->name, 'line_id' => $saldoInicialLine->id,
            'editable' => true, 'editable_zero_only' => true,
            'values' => $lineValues($saldoInicialLine),
        ];

        $rows[] = ['type' => 'section', 'label' => 'Entradas'];
        foreach ($entradasSections as $sec) {
            foreach ($sec->lines as $line) {
                $rows[] = ['type' => 'data', 'label' => $line->name, 'line_id' => $line->id, 'editable' => true, 'values' => $lineValues($line)];
            }
        }
        $rows[] = [
            'type' => 'total', 'label' => 'Total de entradas',
            'values' => collect($periods)->mapWithKeys(fn ($idx) => [$idx => ['ppto' => $totalEntradas['budgeted'][$idx], 'real' => $totalEntradas['actual'][$idx]]])->all(),
        ];
        $rows[] = [
            'type' => 'highlight', 'label' => '$$ Disponible',
            'values' => collect($periods)->mapWithKeys(fn ($idx) => [$idx => [
                'ppto' => $saldoInicialLine->getValueForPeriod($idx, 'budgeted') + $totalEntradas['budgeted'][$idx],
                'real' => $saldoInicialLine->getValueForPeriod($idx, 'actual') + $totalEntradas['actual'][$idx],
            ]])->all(),
        ];

        $rows[] = ['type' => 'section', 'label' => 'Salidas'];
        foreach ($salidasSections as $sec) {
            foreach ($sec->lines as $line) {
                $rows[] = ['type' => 'data', 'label' => $line->name, 'line_id' => $line->id, 'editable' => true, 'is_outflow' => true, 'values' => $lineValues($line)];
            }
        }
        $rows[] = [
            'type' => 'total', 'label' => 'Total de salidas', 'is_outflow' => true,
            'values' => collect($periods)->mapWithKeys(fn ($idx) => [$idx => ['ppto' => $totalSalidas['budgeted'][$idx], 'real' => $totalSalidas['actual'][$idx]]])->all(),
        ];
        $rows[] = [
            'type' => 'highlight', 'label' => 'Sobrante (o faltante) del mes',
            'values' => collect($periods)->mapWithKeys(fn ($idx) => [$idx => [
                'ppto' => $totalEntradas['budgeted'][$idx] - $totalSalidas['budgeted'][$idx],
                'real' => $totalEntradas['actual'][$idx] - $totalSalidas['actual'][$idx],
            ]])->all(),
        ];

        $rows[] = [
            'type' => 'final', 'label' => $saldoFinalLine->name, 'line_id' => null, 'editable' => false,
            'values' => $lineValues($saldoFinalLine),
        ];

        $chartCumplimiento = collect($periods)->map(function ($idx) use ($saldoFinalLine) {
            $p = $saldoFinalLine->getValueForPeriod($idx, 'budgeted');
            $r = $saldoFinalLine->getValueForPeriod($idx, 'actual');
            return $p != 0.0 ? round(($r / $p) * 100, 1) : null;
        })->values()->all();

        return [
            'rows'              => $rows,
            'chartLabels'       => array_values($periodLabels),
            'chartPpto'         => collect($periods)->map(fn ($idx) => round($saldoFinalLine->getValueForPeriod($idx, 'budgeted')))->values()->all(),
            'chartReal'         => collect($periods)->map(fn ($idx) => round($saldoFinalLine->getValueForPeriod($idx, 'actual')))->values()->all(),
            'chartCumplimiento' => $chartCumplimiento,
        ];
    }

    /**
     * Secciones agrupadas por `statement_role` (no por nombre, que el usuario
     * puede editar libremente) — usado por `buildEsfReport()`/`buildEriReport()`.
     */
    private function sectionsByRole(): \Illuminate\Support\Collection
    {
        return $this->sections->groupBy(fn (BudgetSection $s) => $s->statement_role ?? 'sin_clasificar');
    }

    /**
     * Suma neta (aplicando `sign_negative` por línea) de todas las secciones
     * de un rol dado, por período.
     */
    private function roleTotals(\Illuminate\Support\Collection $roleGroups, string $role, array $periods): array
    {
        $totals = array_fill_keys($periods, 0.0);
        foreach ($roleGroups->get($role, collect()) as $section) {
            foreach ($section->lines as $line) {
                foreach ($periods as $idx) {
                    $val = $line->getValueForPeriod($idx);
                    $totals[$idx] += $line->sign_negative ? -$val : $val;
                }
            }
        }
        return $totals;
    }

    /**
     * `$groupLabel` es el subtítulo de grupo ya agregado justo antes de
     * llamar esta función — el catálogo por defecto de Estados Financieros
     * nombra la sección igual que su rol (p. ej. sección "Ingresos
     * Operacionales" bajo el grupo "INGRESOS OPERACIONALES"), así que sin
     * este chequeo el subtítulo se veía duplicado dos veces seguidas (bug
     * real reportado por el usuario). Si el usuario renombra la sección o
     * agrega una adicional bajo el mismo rol (flujo heredado de ESF/ERI
     * sueltos, donde sí se puede), su nombre deja de coincidir y se sigue
     * mostrando con normalidad.
     */
    private function addRoleRows(array &$rows, \Illuminate\Support\Collection $roleGroups, string $role, array $periods, string $groupLabel): void
    {
        foreach ($roleGroups->get($role, collect()) as $section) {
            if (strcasecmp(trim($section->name), trim($groupLabel)) !== 0) {
                $rows[] = ['type' => 'section', 'label' => $section->name];
            }
            foreach ($section->lines as $line) {
                $values = [];
                foreach ($periods as $idx) {
                    $values[$idx] = $line->getValueForPeriod($idx);
                }
                $rows[] = [
                    'type' => 'data', 'label' => $line->name, 'line_id' => $line->id,
                    'sign_negative' => $line->sign_negative, 'values' => $values,
                ];
            }
        }
    }

    private static function sumTotals(array $a, array $b): array
    {
        $out = [];
        foreach ($a as $idx => $v) {
            $out[$idx] = $v + ($b[$idx] ?? 0.0);
        }
        return $out;
    }

    /**
     * Estado de Situación Financiera: Activo (corriente + no corriente),
     * Pasivo (corriente + no corriente), Patrimonio, y el cuadre
     * Activo = Pasivo + Patrimonio (nunca se fuerza — si no cuadra, se
     * expone la diferencia tal cual para que el contador la revise).
     * Requiere `sections.lines.values` precargado.
     */
    public function buildEsfReport(): ?array
    {
        if ($this->type !== 'esf' || $this->sections->isEmpty()) {
            return null;
        }

        $periodLabels = $this->getPeriodLabels();
        $periods      = array_keys($periodLabels);
        $roleGroups   = $this->sectionsByRole();
        $rows         = [];

        $activoCorriente    = $this->roleTotals($roleGroups, 'activo_corriente', $periods);
        $activoNoCorriente  = $this->roleTotals($roleGroups, 'activo_no_corriente', $periods);
        $totalActivo        = self::sumTotals($activoCorriente, $activoNoCorriente);
        $pasivoCorriente     = $this->roleTotals($roleGroups, 'pasivo_corriente', $periods);
        $pasivoNoCorriente   = $this->roleTotals($roleGroups, 'pasivo_no_corriente', $periods);
        $totalPasivo         = self::sumTotals($pasivoCorriente, $pasivoNoCorriente);
        $totalPatrimonio     = $this->roleTotals($roleGroups, 'patrimonio', $periods);
        $totalPasivoPatrimonio = self::sumTotals($totalPasivo, $totalPatrimonio);
        $diferencia = [];
        foreach ($periods as $idx) {
            $diferencia[$idx] = round($totalActivo[$idx] - $totalPasivoPatrimonio[$idx], 2);
        }

        $rows[] = ['type' => 'group', 'label' => 'Activos'];
        $this->addRoleRows($rows, $roleGroups, 'activo_corriente', $periods, 'Activos');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL ACTIVO CORRIENTE', 'values' => $activoCorriente];
        $this->addRoleRows($rows, $roleGroups, 'activo_no_corriente', $periods, 'Activos');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL ACTIVO NO CORRIENTE', 'values' => $activoNoCorriente];
        $rows[] = ['type' => 'highlight', 'key' => 'total_activo', 'label' => 'TOTAL ACTIVO', 'values' => $totalActivo];

        $rows[] = ['type' => 'group', 'label' => 'Pasivos'];
        $this->addRoleRows($rows, $roleGroups, 'pasivo_corriente', $periods, 'Pasivos');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL PASIVO CORRIENTE', 'values' => $pasivoCorriente];
        $this->addRoleRows($rows, $roleGroups, 'pasivo_no_corriente', $periods, 'Pasivos');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL PASIVO NO CORRIENTE', 'values' => $pasivoNoCorriente];
        $rows[] = ['type' => 'highlight', 'label' => 'TOTAL PASIVO', 'values' => $totalPasivo];

        $rows[] = ['type' => 'group', 'label' => 'Patrimonio'];
        $this->addRoleRows($rows, $roleGroups, 'patrimonio', $periods, 'Patrimonio');
        $rows[] = ['type' => 'highlight', 'label' => 'TOTAL PATRIMONIO', 'values' => $totalPatrimonio];

        $rows[] = ['type' => 'highlight', 'label' => 'TOTAL PASIVO + PATRIMONIO', 'values' => $totalPasivoPatrimonio];
        $rows[] = ['type' => 'balance', 'label' => 'Diferencia (Activo − Pasivo − Patrimonio)', 'values' => $diferencia];

        return [
            'periodLabels'         => $periodLabels,
            'rows'                 => $rows,
            'diferencia'           => $diferencia,
            'totalActivoCorriente' => $activoCorriente,
            'totalPasivoCorriente' => $pasivoCorriente,
            'totalActivo'          => $totalActivo,
            'totalPasivo'          => $totalPasivo,
            'totalPatrimonio'      => $totalPatrimonio,
        ];
    }

    /**
     * Estado de Resultados: cascada Ventas Netas → Costo de Ventas →
     * Utilidad Bruta → Gastos Operacionales → EBIT → No Operacionales → UAI →
     * Impuestos → Utilidad Neta → ORI → Resultado Integral Total.
     * Requiere `sections.lines.values` precargado.
     */
    public function buildEriReport(): ?array
    {
        if ($this->type !== 'eri' || $this->sections->isEmpty()) {
            return null;
        }

        $periodLabels = $this->getPeriodLabels();
        $periods      = array_keys($periodLabels);
        $roleGroups   = $this->sectionsByRole();
        $rows         = [];

        $ventasNetas   = $this->roleTotals($roleGroups, 'ingresos_operacionales', $periods);
        $costoVentas   = $this->roleTotals($roleGroups, 'costo_ventas', $periods);
        $utilidadBruta = self::sumTotals($ventasNetas, $costoVentas);
        $gastosAdmon   = $this->roleTotals($roleGroups, 'gastos_administracion', $periods);
        $gastosVentas  = $this->roleTotals($roleGroups, 'gastos_ventas', $periods);
        $totalGastosOp = self::sumTotals($gastosAdmon, $gastosVentas);
        $ebit          = self::sumTotals($utilidadBruta, $totalGastosOp);
        $depreciacion  = array_fill_keys($periods, 0.0);
        foreach ($this->sections as $section) {
            foreach ($section->lines as $line) {
                if ($line->name === self::ERI_DEPRECIACION_GASTO_LINE) {
                    foreach ($periods as $idx) {
                        $depreciacion[$idx] += $line->getValueForPeriod($idx);
                    }
                }
            }
        }
        $ebitda        = self::sumTotals($ebit, $depreciacion);
        $ingresosNoOp  = $this->roleTotals($roleGroups, 'ingresos_no_operacionales', $periods);
        $gastosNoOp    = $this->roleTotals($roleGroups, 'gastos_no_operacionales', $periods);
        $uai           = self::sumTotals(self::sumTotals($ebit, $ingresosNoOp), $gastosNoOp);
        $impuestos     = $this->roleTotals($roleGroups, 'impuestos', $periods);
        $utilidadNeta  = self::sumTotals($uai, $impuestos);
        $ori           = $this->roleTotals($roleGroups, 'ori', $periods);
        $resultadoIntegral = self::sumTotals($utilidadNeta, $ori);

        $rows[] = ['type' => 'group', 'label' => 'INGRESOS OPERACIONALES'];
        $this->addRoleRows($rows, $roleGroups, 'ingresos_operacionales', $periods, 'INGRESOS OPERACIONALES');
        $rows[] = ['type' => 'total', 'label' => 'VENTAS NETAS', 'values' => $ventasNetas];

        $rows[] = ['type' => 'group', 'label' => 'COSTO DE VENTAS'];
        $this->addRoleRows($rows, $roleGroups, 'costo_ventas', $periods, 'COSTO DE VENTAS');
        $rows[] = ['type' => 'total', 'label' => 'COSTO DE VENTAS', 'values' => $costoVentas];
        $rows[] = ['type' => 'highlight', 'label' => 'UTILIDAD BRUTA', 'values' => $utilidadBruta];

        $rows[] = ['type' => 'group', 'label' => 'GASTOS OPERACIONALES DE ADMINISTRACIÓN'];
        $this->addRoleRows($rows, $roleGroups, 'gastos_administracion', $periods, 'GASTOS OPERACIONALES DE ADMINISTRACIÓN');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL GASTOS ADMINISTRACIÓN', 'values' => $gastosAdmon];

        $rows[] = ['type' => 'group', 'label' => 'GASTOS OPERACIONALES DE VENTAS'];
        $this->addRoleRows($rows, $roleGroups, 'gastos_ventas', $periods, 'GASTOS OPERACIONALES DE VENTAS');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL GASTOS DE VENTAS', 'values' => $gastosVentas];
        $rows[] = ['type' => 'highlight', 'label' => 'UTILIDAD OPERACIONAL (EBIT)', 'values' => $ebit];
        $rows[] = ['type' => 'highlight', 'label' => 'EBITDA', 'values' => $ebitda];

        $rows[] = ['type' => 'group', 'label' => 'INGRESOS NO OPERACIONALES'];
        $this->addRoleRows($rows, $roleGroups, 'ingresos_no_operacionales', $periods, 'INGRESOS NO OPERACIONALES');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL INGRESOS NO OPERACIONALES', 'values' => $ingresosNoOp];

        $rows[] = ['type' => 'group', 'label' => 'GASTOS NO OPERACIONALES'];
        $this->addRoleRows($rows, $roleGroups, 'gastos_no_operacionales', $periods, 'GASTOS NO OPERACIONALES');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL GASTOS NO OPERACIONALES', 'values' => $gastosNoOp];
        $rows[] = ['type' => 'highlight', 'label' => 'UTILIDAD ANTES DE IMPUESTOS (UAI)', 'values' => $uai];

        $rows[] = ['type' => 'group', 'label' => 'PROVISIÓN PARA IMPUESTOS'];
        $this->addRoleRows($rows, $roleGroups, 'impuestos', $periods, 'PROVISIÓN PARA IMPUESTOS');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL IMPUESTO DE RENTA', 'values' => $impuestos];
        $rows[] = ['type' => 'highlight', 'key' => self::ERI_UTILIDAD_NETA_KEY, 'label' => 'UTILIDAD NETA DEL PERÍODO', 'values' => $utilidadNeta];

        $rows[] = ['type' => 'group', 'label' => 'OTRO RESULTADO INTEGRAL (ORI)'];
        $this->addRoleRows($rows, $roleGroups, 'ori', $periods, 'OTRO RESULTADO INTEGRAL (ORI)');
        $rows[] = ['type' => 'total', 'label' => 'TOTAL ORI', 'values' => $ori];
        $rows[] = ['type' => 'highlight', 'label' => 'RESULTADO INTEGRAL TOTAL DEL PERÍODO', 'values' => $resultadoIntegral];

        return [
            'periodLabels'  => $periodLabels,
            'rows'          => $rows,
            'ventasNetas'   => $ventasNetas,
            'utilidadBruta' => $utilidadBruta,
            'utilidadNeta'  => $utilidadNeta,
            'ebit'          => $ebit,
            'ebitda'        => $ebitda,
        ];
    }

    /**
     * Filtra las filas de un reporte ESF/ERI para PDF/impresión (nunca para
     * la vista editable en pantalla):
     * 1) Quita la fila de cuadre ("Diferencia") y "TOTAL PASIVO + PATRIMONIO"
     *    (redundante junto a "TOTAL PASIVO" y "TOTAL PATRIMONIO", ya visibles).
     * 2) En el ERI, oculta el detalle de inventarios (inicial/compras/final)
     *    que arma el costo de ventas — solo se muestra el total "COSTO DE
     *    VENTAS" ya calculado.
     * 3) Cuando un grupo tiene una única sección con el mismo nombre (sin
     *    espacios ni mayúsculas) — el caso normal en el ERI, donde cada rol
     *    tiene una sola sección, y en "Patrimonio" del ESF — el encabezado de
     *    sección repite literalmente el del grupo; se oculta la sección y se
     *    deja solo el grupo. Los grupos con secciones genuinamente distintas
     *    (p. ej. "ACTIVOS" con "Activo Corriente"/"Activo No Corriente" en el
     *    ESF) no se ven afectados.
     * 4) Si tras lo anterior un grupo o sección se queda sin ningún rubro
     *    visible debajo (p. ej. "Costo de Ventas", cuyo único contenido eran
     *    los 3 renglones de inventario), también se oculta ese encabezado en
     *    vez de repetir la misma etiqueta seguida sin cifra.
     */
    public static function filterStatementRowsForPrint(array $rows, bool $isEri): array
    {
        $hiddenEriLines = ['Inventario inicial', 'Compras netas del período', 'Inventario final'];
        $normalize = fn (string $s) => str_replace(' ', '', mb_strtoupper(trim($s), 'UTF-8'));

        $rows = array_values(array_filter($rows, function ($row) use ($isEri, $hiddenEriLines) {
            if ($row['type'] === 'balance' || $row['label'] === 'TOTAL PASIVO + PATRIMONIO') {
                return false;
            }

            return !($isEri && $row['type'] === 'data' && in_array($row['label'], $hiddenEriLines, true));
        }));

        $lastGroupLabel = null;
        $rows = array_values(array_filter($rows, function ($row) use (&$lastGroupLabel, $normalize) {
            if ($row['type'] === 'group') {
                $lastGroupLabel = $row['label'];

                return true;
            }

            if ($row['type'] === 'section' && $lastGroupLabel !== null && $normalize($row['label']) === $normalize($lastGroupLabel)) {
                return false;
            }

            return true;
        }));

        $hasDataBeforeNextGroup = function (int $from) use ($rows) {
            for ($j = $from; $j < count($rows); $j++) {
                if ($rows[$j]['type'] === 'group') {
                    return false;
                }
                if ($rows[$j]['type'] === 'data') {
                    return true;
                }
            }

            return false;
        };

        return array_values(array_filter($rows, function ($row, $i) use ($rows, $hasDataBeforeNextGroup) {
            if ($row['type'] === 'section') {
                $next = $rows[$i + 1] ?? null;

                return $next && $next['type'] === 'data';
            }

            if ($row['type'] === 'group') {
                return $hasDataBeforeNextGroup($i + 1);
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH));
    }
}
