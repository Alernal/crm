<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_obligation_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->enum('periodicity', ['mensual', 'bimestral', 'cuatrimestral', 'anual', 'unica']);
            $table->enum('nit_reference', ['ultimo_digito', 'dos_ultimos_digitos'])->default('ultimo_digito');
            $table->enum('regime', ['ordinario', 'simple', 'ambos'])->default('ambos');
            $table->string('responsibility_key', 64)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tax_due_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obligation_type_id')->constrained('tax_obligation_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('period_number');
            $table->string('period_label', 64);
            $table->string('nit_key', 10);
            $table->date('due_date');
            $table->timestamps();
            $table->unique(['obligation_type_id', 'year', 'period_number', 'nit_key'], 'unique_tax_due_date');
        });

        Schema::create('tax_calendar_pdfs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('original_name', 255);
            $table->string('path', 512);
            $table->enum('status', ['pending', 'reviewed', 'imported', 'failed'])->default('pending');
            $table->json('extracted_data')->nullable();
            $table->text('parse_notes')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        $this->seedObligations();
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_calendar_pdfs');
        Schema::dropIfExists('tax_due_dates');
        Schema::dropIfExists('tax_obligation_types');
    }

    private function seedObligations(): void
    {
        $obligations = [
            ['code' => 'IVA_BI',     'name' => 'IVA Bimestral',                      'periodicity' => 'bimestral',     'nit_reference' => 'ultimo_digito',      'regime' => 'ordinario', 'responsibility_key' => 'IVA',        'sort_order' => 1],
            ['code' => 'IVA_C4',     'name' => 'IVA Cuatrimestral',                   'periodicity' => 'cuatrimestral', 'nit_reference' => 'ultimo_digito',      'regime' => 'ordinario', 'responsibility_key' => 'IVA',        'sort_order' => 2],
            ['code' => 'RTEFTE',     'name' => 'Retefuente Mensual',                   'periodicity' => 'mensual',       'nit_reference' => 'ultimo_digito',      'regime' => 'ordinario', 'responsibility_key' => 'Retefuente', 'sort_order' => 3],
            ['code' => 'ICA',        'name' => 'ICA – Industria y Comercio',           'periodicity' => 'bimestral',     'nit_reference' => 'ultimo_digito',      'regime' => 'ordinario', 'responsibility_key' => 'ICA',        'sort_order' => 4],
            ['code' => 'RENTA_JUR',  'name' => 'Renta Personas Jurídicas',             'periodicity' => 'anual',         'nit_reference' => 'dos_ultimos_digitos','regime' => 'ordinario', 'responsibility_key' => 'Renta',      'sort_order' => 5],
            ['code' => 'RENTA_NAT',  'name' => 'Renta Personas Naturales',             'periodicity' => 'anual',         'nit_reference' => 'dos_ultimos_digitos','regime' => 'ordinario', 'responsibility_key' => 'Renta',      'sort_order' => 6],
            ['code' => 'EXOGENA',    'name' => 'Información Exógena',                  'periodicity' => 'anual',         'nit_reference' => 'dos_ultimos_digitos','regime' => 'ambos',     'responsibility_key' => 'Exogena',    'sort_order' => 7],
            ['code' => 'INC',        'name' => 'Impoconsumo',                          'periodicity' => 'bimestral',     'nit_reference' => 'ultimo_digito',      'regime' => 'ordinario', 'responsibility_key' => 'INC',        'sort_order' => 8],
            ['code' => 'SIMPLE_ANT', 'name' => 'Anticipo Bimestral SIMPLE',            'periodicity' => 'bimestral',     'nit_reference' => 'ultimo_digito',      'regime' => 'simple',    'responsibility_key' => 'SIMPLE',     'sort_order' => 9],
            ['code' => 'SIMPLE_IVA', 'name' => 'IVA Anual Régimen SIMPLE',             'periodicity' => 'anual',         'nit_reference' => 'ultimo_digito',      'regime' => 'simple',    'responsibility_key' => 'SIMPLE',     'sort_order' => 10],
            ['code' => 'SIMPLE_DEC', 'name' => 'Declaración Anual Consolidada SIMPLE', 'periodicity' => 'anual',         'nit_reference' => 'ultimo_digito',      'regime' => 'simple',    'responsibility_key' => 'SIMPLE',     'sort_order' => 11],
        ];

        foreach ($obligations as $ob) {
            \DB::table('tax_obligation_types')->insert(array_merge($ob, [
                'description' => null,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]));
        }

        $this->seedDatesFromConfig();
    }

    private function seedDatesFromConfig(): void
    {
        $config = config('tax_calendar_2026');
        if (! $config) return;

        $year = 2026;

        $mapping = [
            'iva_bimestral'     => 'IVA_BI',
            'iva_cuatrimestral' => 'IVA_C4',
            'retefuente'        => 'RTEFTE',
            'renta_juridicas'   => 'RENTA_JUR',
            'renta_naturales'   => 'RENTA_NAT',
            'simple_anticipos'  => 'SIMPLE_ANT',
        ];

        foreach ($mapping as $configKey => $code) {
            if (empty($config[$configKey])) continue;

            $obligation = \DB::table('tax_obligation_types')->where('code', $code)->first();
            if (! $obligation) continue;

            foreach ($config[$configKey] as $periodIdx => $period) {
                $periodNumber = $periodIdx + 1;
                $periodLabel  = $period['period'] ?? $period['label'] ?? "Período {$periodNumber}";
                $dates        = $period['dates'] ?? [];

                if (isset($dates['two_digits'])) {
                    foreach ($dates['two_digits'] as $range => $date) {
                        if (! $date) continue;
                        \DB::table('tax_due_dates')->insertOrIgnore([
                            'obligation_type_id' => $obligation->id,
                            'year'               => $year,
                            'period_number'      => $periodNumber,
                            'period_label'       => $periodLabel,
                            'nit_key'            => (string) $range,
                            'due_date'           => $date,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                } else {
                    foreach ($dates as $digit => $date) {
                        if (! $date) continue;
                        \DB::table('tax_due_dates')->insertOrIgnore([
                            'obligation_type_id' => $obligation->id,
                            'year'               => $year,
                            'period_number'      => $periodNumber,
                            'period_label'       => $periodLabel,
                            'nit_key'            => (string) $digit,
                            'due_date'           => $date,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                }
            }
        }
    }
};
