<?php

namespace App\Services;

use App\Models\TaxObligationType;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parsea el Excel/CSV oficial del Calendario Tributario DIAN (una hoja por impuesto)
 * y lo convierte en filas listas para TaxDueDate::updateOrCreate().
 *
 * Cada hoja del archivo DIAN tiene una de estas formas:
 *  - grilla por dígito único (0-9): Renta GC/PJ, IVA Bi/Cuatrimestral, Retefuente, RST Anticipo
 *  - filas por rango de dos dígitos: Renta Personas Naturales
 *  - grilla por banda de dos dígitos (5 columnas): RST Declaración Anual
 *  - una fecha fija por período: Gasolina, Carbono, IVA Servicios Exterior, Bebidas
 *  - formas mixtas puntuales: PES, Patrimonio, Precios de Transferencia, Plásticos, RUB
 *  - alias sin fecha propia: Consumo, Activos en el Exterior
 */
class TaxCalendarImportParser
{
    private const MONTHS = [
        'ene' => 1, 'enero' => 1, 'feb' => 2, 'febrero' => 2, 'mar' => 3, 'marzo' => 3,
        'abr' => 4, 'abril' => 4, 'may' => 5, 'mayo' => 5, 'jun' => 6, 'junio' => 6,
        'jul' => 7, 'julio' => 7, 'ago' => 8, 'agosto' => 8, 'sep' => 9, 'septiembre' => 9,
        'oct' => 10, 'octubre' => 10, 'nov' => 11, 'noviembre' => 11, 'dic' => 12, 'diciembre' => 12,
    ];

    private const SHEET_HANDLERS = [
        'renta grandes contribuyentes' => 'parseGridDigit',
        'renta personas juridicas'     => 'parseGridDigit',
        'iva bimestral'                => 'parseGridDigit',
        'iva cuatrimestral'            => 'parseGridDigit',
        'retencion en la fuente'       => 'parseGridDigit',
        'rst anticipo bimestral'       => 'parseGridDigit',
        'renta personas naturales'     => 'parseTwoDigitRows',
        'rst declaracion anual'        => 'parseFiveBandGrid',
        'gasolina y acpm'              => 'parseSingleDatePerPeriod',
        'carbono'                      => 'parseSingleDatePerPeriod',
        'iva servicios exterior'       => 'parseSingleDatePerPeriod',
        'bebidas ultraprocesadas'      => 'parseSingleDatePerPeriod',
        'pes'                          => 'parsePes',
        'patrimonio'                   => 'parsePatrimonio',
        'precios de transferencia'     => 'parsePreciosTransferencia',
        'plasticos de un solo uso'     => 'parsePlasticos',
        'rub'                          => 'parseRub',
        'consumo'                      => 'parseAliasNote',
        'activos en el exterior'       => 'parseAliasNote',
    ];

    private const SHEET_CODE = [
        'renta grandes contribuyentes' => 'RENTA_GC',
        'renta personas juridicas'     => 'RENTA_JUR',
        'iva bimestral'                => 'IVA_BI',
        'iva cuatrimestral'            => 'IVA_C4',
        'retencion en la fuente'       => 'RTEFTE',
        'rst anticipo bimestral'       => 'SIMPLE_ANT',
        'gasolina y acpm'              => 'GASOLINA',
        'carbono'                      => 'CARBONO',
        'iva servicios exterior'       => 'IVA_EXT',
        'bebidas ultraprocesadas'      => 'BEBIDAS',
    ];

    public function parse(string $filePath, int $year): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows        = [];
        $summary     = ['matched' => [], 'skipped' => [], 'rows_by_code' => []];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $title      = $sheet->getTitle();
            $normalized = $this->normalize($title);
            $handlerKey = $this->matchHandler($normalized);

            if (! $handlerKey) {
                $summary['skipped'][] = "{$title}: nombre de hoja no reconocido.";
                continue;
            }

            $method = self::SHEET_HANDLERS[$handlerKey];
            $data   = $sheet->toArray(null, true, true, false);

            try {
                $sheetRows = $this->{$method}($data, $year, $handlerKey);
            } catch (\Throwable $e) {
                $summary['skipped'][] = "{$title}: error al parsear ({$e->getMessage()}).";
                continue;
            }

            if (empty($sheetRows)) {
                $note = $method === 'parseAliasNote'
                    ? "{$title}: sin fecha propia (hereda el vencimiento de otra obligación automáticamente)."
                    : "{$title}: sin filas detectadas (revise el formato).";
                $summary['skipped'][] = $note;
                continue;
            }

            $summary['matched'][] = $title;
            foreach ($sheetRows as $r) {
                $rows[] = $r;
                $summary['rows_by_code'][$r['code']] = ($summary['rows_by_code'][$r['code']] ?? 0) + 1;
            }
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function matchHandler(string $normalized): ?string
    {
        if (isset(self::SHEET_HANDLERS[$normalized])) return $normalized;

        foreach (self::SHEET_HANDLERS as $alias => $method) {
            if (str_contains($normalized, $alias) || str_contains($alias, $normalized)) {
                return $alias;
            }
        }

        return null;
    }

    // ── Formas de hoja ──────────────────────────────────────────────────

    private function parseGridDigit(array $data, int $year, string $handlerKey): array
    {
        $code      = self::SHEET_CODE[$handlerKey];
        $headerIdx = $this->findRowContaining($data, ['mes limite']);
        if ($headerIdx === null) return [];

        $digitCols = $this->digitColumnMap($data[$headerIdx]);
        $rows      = [];
        $periodNum = 0;

        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row       = $data[$i];
            $label     = trim((string) ($row[0] ?? ''));
            $mesLimite = trim((string) ($row[1] ?? ''));
            if ($label === '' || $mesLimite === '') continue;

            $periodNum++;
            $rowYear = $this->extractYear($mesLimite, $year);

            foreach ($digitCols as $col => $digit) {
                $date = $this->parseDayMonth((string) ($row[$col] ?? ''), $rowYear);
                if (! $date) continue;

                $rows[] = ['code' => $code, 'period_number' => $periodNum, 'period_label' => $label, 'nit_key' => $digit, 'due_date' => $date];
            }
        }

        return $rows;
    }

    private function parseTwoDigitRows(array $data, int $year): array
    {
        $headerIdx = $this->findRowContaining($data, ['digitos nit']);
        if ($headerIdx === null) return [];

        $rows = [];
        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row   = $data[$i];
            $pair  = trim((string) ($row[0] ?? ''));
            $mes   = trim((string) ($row[1] ?? ''));
            $fecha = trim((string) ($row[3] ?? ($row[2] ?? '')));
            if (! preg_match('/^\d{2}-\d{2}$/', $pair)) continue;

            $rowYear = $this->extractYear($mes, $year);
            $date    = $this->parseFlexibleDate($fecha, $rowYear);
            if (! $date) continue;

            $rows[] = ['code' => 'RENTA_NAT', 'period_number' => 1, 'period_label' => 'Declaración y pago', 'nit_key' => $pair, 'due_date' => $date];
        }

        return $rows;
    }

    private function parseFiveBandGrid(array $data, int $year): array
    {
        $headerIdx = $this->findRowContaining($data, ['mes limite']);
        if ($headerIdx === null) return [];

        $bandCols = [];
        foreach ($data[$headerIdx] as $col => $cell) {
            if (preg_match('/(\d)\s*-\s*0?(\d)/', (string) $cell, $m)) {
                $bandCols[$col] = [$m[1], $m[2]];
            }
        }

        $rows = [];
        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row       = $data[$i];
            $tipo      = trim((string) ($row[0] ?? ''));
            $mesLimite = trim((string) ($row[1] ?? ''));
            if ($tipo === '' || $mesLimite === '') continue;

            $code    = str_contains(mb_strtolower($tipo), 'iva') ? 'SIMPLE_IVA' : 'SIMPLE_DEC';
            $rowYear = $this->extractYear($mesLimite, $year);

            foreach ($bandCols as $col => $digits) {
                $date = $this->parseDayMonth((string) ($row[$col] ?? ''), $rowYear);
                if (! $date) continue;

                foreach ($digits as $digit) {
                    $rows[] = ['code' => $code, 'period_number' => 1, 'period_label' => $tipo, 'nit_key' => $digit, 'due_date' => $date];
                }
            }
        }

        return $rows;
    }

    private function parseSingleDatePerPeriod(array $data, int $year, string $handlerKey): array
    {
        $code      = self::SHEET_CODE[$handlerKey];
        $headerIdx = $this->findRowContaining($data, ['fecha limite']);
        if ($headerIdx === null) return [];

        $rows      = [];
        $periodNum = 0;
        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row       = $data[$i];
            $label     = trim((string) ($row[0] ?? ''));
            $mesLimite = trim((string) ($row[1] ?? ''));
            $fecha     = trim((string) ($row[2] ?? ''));
            if ($label === '' || $fecha === '') continue;

            $periodNum++;
            $rowYear = $this->extractYear($mesLimite, $year);
            $date    = $this->parseFlexibleDate($fecha, $rowYear);
            if (! $date) continue;

            $rows[] = ['code' => $code, 'period_number' => $periodNum, 'period_label' => $label, 'nit_key' => TaxObligationType::FIXED_DATE_KEY, 'due_date' => $date];
        }

        return $rows;
    }

    private function parsePes(array $data, int $year): array
    {
        $headerIdx = $this->findRowContaining($data, ['fecha limite']);
        if ($headerIdx === null) return [];

        $rows        = [];
        $anticipoNum = 0;
        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row     = $data[$i];
            $tipo    = trim((string) ($row[0] ?? ''));
            $periodo = trim((string) ($row[1] ?? ''));
            $fecha   = trim((string) ($row[2] ?? ''));
            if ($tipo === '' || $fecha === '') continue;

            $rowYear = $this->extractYear($fecha . ' ' . $periodo, $year);
            $date    = $this->parseFlexibleDate($fecha, $rowYear);
            if (! $date) continue;

            if (str_contains(mb_strtolower($tipo), 'anticipado')) {
                $anticipoNum++;
                $rows[] = ['code' => 'PES_ANT', 'period_number' => $anticipoNum, 'period_label' => $periodo, 'nit_key' => TaxObligationType::FIXED_DATE_KEY, 'due_date' => $date];
            } else {
                $rows[] = ['code' => 'PES_DEC', 'period_number' => 1, 'period_label' => $tipo, 'nit_key' => TaxObligationType::FIXED_DATE_KEY, 'due_date' => $date];
            }
        }

        return $rows;
    }

    private function parsePatrimonio(array $data, int $year): array
    {
        $headerIdx = $this->findRowContaining($data, ['mes limite']);
        if ($headerIdx === null) return [];

        $digitCols = $this->digitColumnMap($data[$headerIdx]);
        $rows      = [];

        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row       = $data[$i];
            $label     = trim((string) ($row[0] ?? ''));
            $mesLimite = trim((string) ($row[1] ?? ''));
            if ($label === '' || $mesLimite === '') continue;

            $rowYear = $this->extractYear($mesLimite, $year);

            $found = [];
            foreach ($digitCols as $col => $digit) {
                $date = $this->parseDayMonth((string) ($row[$col] ?? ''), $rowYear);
                if ($date) $found[$digit] = $date;
            }

            if (count($found) > 1) {
                foreach ($found as $digit => $date) {
                    $rows[] = ['code' => 'PATRIMONIO', 'period_number' => 1, 'period_label' => $label, 'nit_key' => $digit, 'due_date' => $date];
                }
            } elseif (count($found) === 1) {
                $rows[] = ['code' => 'PATRIMONIO', 'period_number' => 2, 'period_label' => $label, 'nit_key' => TaxObligationType::FIXED_DATE_KEY, 'due_date' => array_values($found)[0]];
            }
        }

        return $rows;
    }

    private function parsePreciosTransferencia(array $data, int $year): array
    {
        $headerIdx = $this->findRowContaining($data, ['mes limite']);
        if ($headerIdx === null) return [];

        $digitCols = $this->digitColumnMap($data[$headerIdx]);
        $rows      = [];

        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row       = $data[$i];
            $label     = trim((string) ($row[0] ?? ''));
            $mesLimite = trim((string) ($row[1] ?? ''));
            if ($label === '' || $mesLimite === '') continue;

            $rowYear = $this->extractYear($mesLimite, $year);
            $lower   = $this->normalize($label);
            $code    = match (true) {
                str_contains($lower, 'informativa')   => 'PT_INFORME',
                str_contains($lower, 'comprobatoria') => 'PT_DOC',
                str_contains($lower, 'pais')          => 'PT_CBC',
                default                                => null,
            };
            if (! $code) continue;

            if ($code === 'PT_CBC') {
                $date = $this->parseFlexibleDate((string) ($row[2] ?? $mesLimite), $rowYear);
                if ($date) {
                    $rows[] = ['code' => $code, 'period_number' => 1, 'period_label' => $label, 'nit_key' => TaxObligationType::FIXED_DATE_KEY, 'due_date' => $date];
                }
                continue;
            }

            foreach ($digitCols as $col => $digit) {
                $date = $this->parseDayMonth((string) ($row[$col] ?? ''), $rowYear);
                if (! $date) continue;
                $rows[] = ['code' => $code, 'period_number' => 1, 'period_label' => $label, 'nit_key' => $digit, 'due_date' => $date];
            }
        }

        return $rows;
    }

    private function parsePlasticos(array $data, int $year): array
    {
        foreach ($data as $row) {
            $c0 = trim((string) ($row[0] ?? ''));
            $c1 = trim((string) ($row[1] ?? ''));
            if (preg_match('/(\d{1,2})\s+de\s+([a-zñ]+)/iu', $c1, $m)) {
                $rowYear = $this->extractYear($c0, $year);
                $month   = self::MONTHS[$this->normalize($m[2])] ?? null;
                if (! $month) continue;

                return [[
                    'code' => 'PLASTICOS', 'period_number' => 1, 'period_label' => 'Presentación y pago',
                    'nit_key' => TaxObligationType::FIXED_DATE_KEY,
                    'due_date' => sprintf('%04d-%02d-%02d', $rowYear, $month, (int) $m[1]),
                ]];
            }
        }

        return [];
    }

    private function parseRub(array $data, int $year): array
    {
        $headerIdx = $this->findRowContaining($data, ['mes limite']);
        if ($headerIdx === null) return [];

        $rows      = [];
        $periodNum = 0;
        for ($i = $headerIdx + 1; $i < count($data); $i++) {
            $row       = $data[$i];
            $mesLimite = trim((string) ($row[0] ?? ''));
            $fecha     = trim((string) ($row[1] ?? ''));
            if ($mesLimite === '' || $fecha === '') continue;

            $periodNum++;
            $rowYear = $this->extractYear($mesLimite, $year);
            $date    = $this->parseFlexibleDate($fecha, $rowYear);
            if (! $date) continue;

            $rows[] = ['code' => 'RUB', 'period_number' => $periodNum, 'period_label' => "Actualización — {$mesLimite}", 'nit_key' => TaxObligationType::FIXED_DATE_KEY, 'due_date' => $date];
        }

        return $rows;
    }

    private function parseAliasNote(): array
    {
        // Consumo (alias de IVA_BI) y Activos en el Exterior (hereda de la Renta/RST del cliente):
        // no tienen fecha propia, se resuelven en tiempo real en TaxCalendarService.
        return [];
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function findRowContaining(array $data, array $needles): ?int
    {
        foreach ($data as $idx => $row) {
            $joined = $this->normalize(implode(' ', array_map('strval', $row)));
            foreach ($needles as $needle) {
                if (str_contains($joined, $needle)) return $idx;
            }
        }

        return null;
    }

    private function digitColumnMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $col => $cell) {
            if (preg_match('/^\d$/', trim((string) $cell))) {
                $map[$col] = trim((string) $cell);
            }
        }

        return $map;
    }

    private function extractYear(string $text, int $fallback): int
    {
        return preg_match('/(20\d{2})/', $text, $m) ? (int) $m[1] : $fallback;
    }

    /** "10-mar" → "2026-03-10" */
    private function parseDayMonth(string $cell, int $year): ?string
    {
        if (! preg_match('/^(\d{1,2})\s*-\s*([a-zñ]{3,})/iu', trim($cell), $m)) {
            return null;
        }
        $month = self::MONTHS[$this->normalize($m[2])] ?? null;

        return $month ? sprintf('%04d-%02d-%02d', $year, $month, (int) $m[1]) : null;
    }

    /** Acepta "10-mar", "10 de marzo" o una fecha ya en formato Y-m-d. */
    private function parseFlexibleDate(string $cell, int $year): ?string
    {
        $cell = trim($cell);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $cell)) return $cell;

        if (preg_match('/(\d{1,2})\s+de\s+([a-zñ]+)/iu', $cell, $m)) {
            $month = self::MONTHS[$this->normalize($m[2])] ?? null;
            return $month ? sprintf('%04d-%02d-%02d', $year, $month, (int) $m[1]) : null;
        }

        return $this->parseDayMonth($cell, $year);
    }
}
