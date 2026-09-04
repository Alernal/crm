<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\GeneratedDocument;
use PHPUnit\Framework\TestCase;

/**
 * Catálogo de especialidades del Contrato de Prestación de Servicios — el
 * título del documento es siempre el mismo sin importar la especialidad
 * (ver GeneratedDocumentController::store()); lo único que cambia por
 * especialidad son los servicios predeterminados del constructor de
 * Objeto del Contrato del wizard (editables después) y el texto de esa
 * misma cláusula (intro + disclaimer).
 */
class GeneratedDocumentEspecialidadesTest extends TestCase
{
    public function test_tributaria_has_the_seven_services_from_the_real_contract(): void
    {
        $especialidades = GeneratedDocument::especialidades();

        $this->assertCount(7, $especialidades['tributaria']['servicios']);
        $this->assertStringContainsString('Planeación tributaria', collect($especialidades['tributaria']['servicios'])->pluck('nombre')->implode(' | '));
    }

    public function test_financiera_has_exactly_the_six_requested_services(): void
    {
        $especialidades = GeneratedDocument::especialidades();
        $names = collect($especialidades['financiera']['servicios'])->pluck('nombre')->all();

        $this->assertCount(6, $names);
        $this->assertStringContainsString('Planeación financiera', $names[0]);
        $this->assertStringContainsString('presupuestos', mb_strtolower($names[1]));
        $this->assertStringContainsString('tesorería', mb_strtolower($names[2]));
        $this->assertStringContainsString('Contabilidad financiera', $names[3]);
        $this->assertStringContainsString('Análisis financiero', $names[4]);
        $this->assertStringContainsString('proyectos de inversión', mb_strtolower($names[5]));
    }

    public function test_combined_specialty_unions_both_service_lists_without_duplication(): void
    {
        $especialidades = GeneratedDocument::especialidades();
        $combined = $especialidades['tributaria_financiera']['servicios'];

        $this->assertCount(13, $combined);
        $this->assertSame(
            [...$especialidades['tributaria']['servicios'], ...$especialidades['financiera']['servicios']],
            $combined
        );
    }

    public function test_none_of_the_specialties_carry_a_per_specialty_title(): void
    {
        foreach (GeneratedDocument::especialidades() as $key => $especialidad) {
            $this->assertArrayNotHasKey('titulo', $especialidad, "La especialidad '{$key}' no debe traer un título propio — el título del contrato es siempre el mismo.");
        }
    }
}
