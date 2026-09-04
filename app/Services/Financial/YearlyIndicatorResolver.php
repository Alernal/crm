<?php

namespace App\Services\Financial;

use App\Models\ClientBudgetYearlyData;

/**
 * Resuelve el valor de un indicador por año calendario (inflación, SMMLV,
 * auxilio de transporte) para un cliente, rellenando los años que el usuario
 * no digitó componiendo el último valor conocido con la inflación de cada año
 * (misma fórmula que `Datos base!D34=C34*(1+D33)` del caso práctico de
 * Actualícese). Los valores explícitamente cargados por el usuario siempre
 * tienen prioridad; esto solo interpola huecos.
 */
class YearlyIndicatorResolver
{
    private array $cache = [];

    public function __construct(private int $clientId)
    {
    }

    public function valueForYear(string $indicator, int $year): float
    {
        $series = $this->seriesFor($indicator);
        if (empty($series)) {
            return 0.0;
        }

        if (isset($series[$year])) {
            return (float) $series[$year];
        }

        $years = array_keys($series);
        if ($year < min($years)) {
            return (float) $series[min($years)];
        }

        $lastYear = max($years);
        $value    = $series[$lastYear];
        $inflRate = $this->seriesFor('inflacion')[$lastYear] ?? (end($series) ?: 0.0);

        for ($y = $lastYear + 1; $y <= $year; $y++) {
            $rate  = $this->seriesFor('inflacion')[$y] ?? $inflRate;
            $value = round($value * (1 + $rate / 100), 2);
        }

        return (float) $value;
    }

    /**
     * Razón valor($toYear) / valor($fromYear) para un indicador de NIVEL
     * (ej. `smmlv`, un valor en pesos) — usada para encadenar el crecimiento
     * de un renglón con driver `smmlv` período a período.
     */
    public function chainFactor(string $indicator, int $fromYear, int $toYear): float
    {
        $base = $this->valueForYear($indicator, $fromYear);
        if ($base == 0.0) {
            return 1.0;
        }

        return $this->valueForYear($indicator, $toYear) / $base;
    }

    /**
     * Factor de crecimiento acumulado por inflación entre dos años: producto
     * de (1 + inflación_año/100) para cada año entre $fromYear+1 y $toYear.
     * A diferencia de `chainFactor`, la inflación es una TASA (%) por año, no
     * un nivel, así que no se puede usar como razón de valores absolutos.
     */
    public function inflationFactor(int $fromYear, int $toYear): float
    {
        if ($toYear <= $fromYear) {
            return 1.0;
        }

        $factor = 1.0;
        for ($y = $fromYear + 1; $y <= $toYear; $y++) {
            $factor *= 1 + $this->valueForYear('inflacion', $y) / 100;
        }

        return $factor;
    }

    private function seriesFor(string $indicator): array
    {
        if (isset($this->cache[$indicator])) {
            return $this->cache[$indicator];
        }

        $rows = ClientBudgetYearlyData::where('client_id', $this->clientId)
            ->where('indicator', $indicator)
            ->orderBy('year')
            ->pluck('value', 'year')
            ->all();

        if ($indicator === 'inflacion' || empty($rows)) {
            return $this->cache[$indicator] = $rows;
        }

        $inflacion = $this->seriesFor('inflacion');
        $years     = array_keys($rows);
        $minYear   = min($years);
        $maxYear   = max($years);
        $series    = [];
        $current   = null;

        for ($y = $minYear; $y <= $maxYear; $y++) {
            if (isset($rows[$y])) {
                $current = (float) $rows[$y];
            } elseif ($current !== null) {
                $rate    = $inflacion[$y] ?? (end($inflacion) ?: 0.0);
                $current = round($current * (1 + $rate / 100), 2);
            }
            if ($current !== null) {
                $series[$y] = $current;
            }
        }

        return $this->cache[$indicator] = $series;
    }
}
