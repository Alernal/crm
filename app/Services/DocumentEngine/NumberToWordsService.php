<?php

namespace App\Services\DocumentEngine;

/**
 * Convierte montos a letras en español para cláusulas de pago
 * ("DOSCIENTOS MIL PESOS ($200.000)"), tal como exige el contrato real
 * analizado. Soporta enteros de 0 a 999.999.999.999 — más que suficiente
 * para honorarios de servicios profesionales.
 */
final class NumberToWordsService
{
    private const UNITS = [
        '', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
        'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve',
    ];

    private const TENS = [
        2 => 'veinte', 3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta',
        6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa',
    ];

    private const HUNDREDS = [
        1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos',
        5 => 'quinientos', 6 => 'seiscientos', 7 => 'setecientos', 8 => 'ochocientos', 9 => 'novecientos',
    ];

    /** Ej: 200000 -> "DOSCIENTOS MIL PESOS M/CTE" */
    public function toPesos(float|int $amount): string
    {
        $amount = (int) round($amount);
        $words = $amount === 0 ? 'cero' : $this->convert($amount);

        return mb_strtoupper($words).' PESOS M/CTE';
    }

    public function convert(int $number): string
    {
        if ($number < 0) {
            return 'menos '.$this->convert(-$number);
        }

        if ($number === 0) {
            return 'cero';
        }

        if ($number < 1_000) {
            return $this->convertHundreds($number);
        }

        if ($number < 1_000_000) {
            return $this->convertThousands($number);
        }

        if ($number < 1_000_000_000) {
            return $this->convertMillions($number);
        }

        return $this->convertBillions($number);
    }

    private function convertHundreds(int $n): string
    {
        if ($n < 30) {
            return self::UNITS[$n];
        }

        if ($n < 100) {
            $tens = intdiv($n, 10);
            $units = $n % 10;

            return $units === 0 ? self::TENS[$tens] : self::TENS[$tens].' y '.self::UNITS[$units];
        }

        if ($n === 100) {
            return 'cien';
        }

        $hundreds = intdiv($n, 100);
        $rest = $n % 100;

        return $rest === 0 ? self::HUNDREDS[$hundreds] : self::HUNDREDS[$hundreds].' '.$this->convertHundreds($rest);
    }

    private function convertThousands(int $n): string
    {
        $thousands = intdiv($n, 1000);
        $rest = $n % 1000;

        $prefix = $thousands === 1 ? 'mil' : $this->apocopeUno($this->convertHundreds($thousands)).' mil';

        return $rest === 0 ? $prefix : $prefix.' '.$this->convertHundreds($rest);
    }

    private function convertMillions(int $n): string
    {
        $millions = intdiv($n, 1_000_000);
        $rest = $n % 1_000_000;

        $prefix = $millions === 1 ? 'un millón' : $this->apocopeUno($this->convertHundreds($millions)).' millones';

        if ($rest === 0) {
            return $prefix;
        }

        $restWords = $rest < 1000 ? $this->convertHundreds($rest) : $this->convertThousands($rest);

        return $prefix.' '.$restWords;
    }

    private function convertBillions(int $n): string
    {
        $billions = intdiv($n, 1_000_000_000);
        $rest = $n % 1_000_000_000;

        $prefix = ($billions === 1 ? 'mil' : $this->apocopeUno($this->convertHundreds($billions)).' mil').' millones';

        return $rest === 0 ? $prefix : $prefix.' '.$this->convertMillions($rest);
    }

    /**
     * Apócope de "uno" ante un multiplicador (mil/millones): "veintiuno mil"
     * no es español correcto, debe ser "veintiún mil" — y "uno mil" -> "un mil".
     */
    private function apocopeUno(string $words): string
    {
        return match (true) {
            str_ends_with($words, 'veintiuno') => substr($words, 0, -9).'veintiún',
            str_ends_with($words, ' uno') => substr($words, 0, -3).'un',
            $words === 'uno' => 'un',
            default => $words,
        };
    }
}
