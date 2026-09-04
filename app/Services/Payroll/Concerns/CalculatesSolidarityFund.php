<?php

namespace App\Services\Payroll\Concerns;

/**
 * Fondo de solidaridad pensional (tabla de la Ley 100 de 1993, vigente
 * mientras la Ley 2381 de 2024 siga suspendida por la Corte Constitucional).
 * Solo aplica si el IBC es igual o superior a 4 smmlv, así que en la mayoría
 * de nóminas de PyMEs da 0.
 */
trait CalculatesSolidarityFund
{
    protected function solidarityFundContribution(float $ibc, float $smlv): float
    {
        if ($smlv <= 0) {
            return 0.0;
        }

        $ibcInSmlv = $ibc / $smlv;

        if ($ibcInSmlv >= 4 && $ibcInSmlv < 16) {
            return round($ibc * 0.010, 2);
        }
        if ($ibcInSmlv >= 16 && $ibcInSmlv <= 17) {
            return round($ibc * 0.012, 2);
        }
        if ($ibcInSmlv > 17 && $ibcInSmlv <= 18) {
            return round($ibc * 0.014, 2);
        }
        if ($ibcInSmlv > 18 && $ibcInSmlv <= 19) {
            return round($ibc * 0.016, 2);
        }
        if ($ibcInSmlv > 19 && $ibcInSmlv <= 20) {
            return round($ibc * 0.018, 2);
        }
        if ($ibcInSmlv > 20) {
            return round($ibc * 0.020, 2);
        }

        return 0.0;
    }
}
