<?php

namespace Tests\Unit\DocumentEngine;

use App\Services\DocumentEngine\NumberToWordsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NumberToWordsServiceTest extends TestCase
{
    private NumberToWordsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NumberToWordsService();
    }

    #[DataProvider('numberProvider')]
    public function test_converts_numbers_to_spanish_words(int $number, string $expected): void
    {
        $this->assertSame($expected, $this->service->convert($number));
    }

    public static function numberProvider(): array
    {
        return [
            [0, 'cero'],
            [1, 'un'],
            [15, 'quince'],
            [21, 'veintiuno'],
            [30, 'treinta'],
            [31, 'treinta y un'],
            [100, 'cien'],
            [101, 'ciento un'],
            [200, 'doscientos'],
            [999, 'novecientos noventa y nueve'],
            [1000, 'mil'],
            [1001, 'mil un'],
            [2000, 'dos mil'],
            [21000, 'veintiún mil'],
            [1_000_000, 'un millón'],
            [2_000_000, 'dos millones'],
            [12_000_000, 'doce millones'],
        ];
    }

    public function test_formats_amount_as_pesos(): void
    {
        $this->assertSame('DOSCIENTOS MIL PESOS M/CTE', $this->service->toPesos(200000));
        $this->assertSame('CERO PESOS M/CTE', $this->service->toPesos(0));
        $this->assertSame('DOCE MILLONES PESOS M/CTE', $this->service->toPesos(12000000));
    }

    public function test_rounds_decimal_amounts(): void
    {
        $this->assertSame('CIEN PESOS M/CTE', $this->service->toPesos(99.6));
    }
}
