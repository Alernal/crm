<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite no permite alterar CHECK/enum in-place: se reconstruye la tabla.
        Schema::rename('tax_obligation_types', 'tax_obligation_types_backup');
        // SQLite no renombra el índice único al renombrar la tabla; se elimina para evitar colisión de nombre.
        DB::statement('DROP INDEX IF EXISTS tax_obligation_types_code_unique');

        Schema::create('tax_obligation_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->enum('periodicity', ['mensual', 'bimestral', 'trimestral', 'cuatrimestral', 'anual', 'unica']);
            $table->enum('nit_reference', ['ultimo_digito', 'dos_ultimos_digitos', 'fecha_fija'])->default('ultimo_digito');
            $table->enum('regime', ['ordinario', 'simple', 'ambos'])->default('ambos');
            $table->string('responsibility_key', 64)->nullable();
            $table->string('group_label', 64)->nullable();
            $table->json('custom_periods')->nullable();
            $table->foreignId('alias_of')->nullable()->constrained('tax_obligation_types')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO tax_obligation_types
                (id, code, name, description, periodicity, nit_reference, regime, responsibility_key, active, sort_order, created_at, updated_at)
            SELECT id, code, name, description, periodicity, nit_reference, regime, responsibility_key, active, sort_order, created_at, updated_at
            FROM tax_obligation_types_backup
        ");

        Schema::drop('tax_obligation_types_backup');

        $this->fixExistingObligations();
        $this->seedNewObligations();
    }

    public function down(): void
    {
        Schema::rename('tax_obligation_types', 'tax_obligation_types_new');

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

        DB::statement("
            INSERT INTO tax_obligation_types
                (id, code, name, description, periodicity, nit_reference, regime, responsibility_key, active, sort_order, created_at, updated_at)
            SELECT id, code, name, description, periodicity, nit_reference, regime, responsibility_key, active, sort_order, created_at, updated_at
            FROM tax_obligation_types_new
            WHERE alias_of IS NULL AND id <= 11
        ");

        Schema::drop('tax_obligation_types_new');
    }

    /**
     * Corrige datos heredados y agrega etiquetas de período/agrupación
     * a las 11 obligaciones que ya existían antes de esta migración.
     */
    private function fixExistingObligations(): void
    {
        $fixes = [
            'IVA_BI'     => ['group_label' => 'IVA'],
            'IVA_C4'     => ['group_label' => 'IVA'],
            'RTEFTE'     => ['group_label' => 'Retención'],
            'ICA'        => ['group_label' => 'ICA'],
            // Bug heredado: Renta Personas Jurídicas vence por ÚLTIMO dígito, no por dos dígitos.
            'RENTA_JUR'  => [
                'name'           => 'Renta — Personas Jurídicas',
                'nit_reference'  => 'ultimo_digito',
                'group_label'    => 'Renta',
                'custom_periods' => json_encode(['1a. Cuota (declaración y pago) — Mayo', '2a. Cuota (pago) — Julio']),
            ],
            'RENTA_NAT'  => ['name' => 'Renta — Personas Naturales', 'group_label' => 'Renta'],
            'EXOGENA'    => ['group_label' => 'Información exógena'],
            'INC'        => ['name' => 'Impoconsumo (Consumo)', 'group_label' => 'Impoconsumo'],
            'SIMPLE_ANT' => ['group_label' => 'Régimen SIMPLE'],
            'SIMPLE_IVA' => [
                'name'           => 'RST — Consolidada de IVA',
                'group_label'    => 'Régimen SIMPLE',
                'custom_periods' => json_encode(['Declaración y pago — Febrero']),
            ],
            'SIMPLE_DEC' => [
                'name'           => 'RST — Declaración Anual Consolidada',
                'group_label'    => 'Régimen SIMPLE',
                'custom_periods' => json_encode(['Declaración y pago — Abril']),
            ],
        ];

        foreach ($fixes as $code => $data) {
            DB::table('tax_obligation_types')->where('code', $code)->update($data);
        }
    }

    private function seedNewObligations(): void
    {
        $ivaBiId = DB::table('tax_obligation_types')->where('code', 'IVA_BI')->value('id');

        // "Consumo" vence en las mismas fechas que IVA Bimestral (regla DIAN) — se resuelve como alias.
        DB::table('tax_obligation_types')->where('code', 'INC')->update(['alias_of' => $ivaBiId]);

        $obligations = [
            [
                'code' => 'RENTA_GC', 'name' => 'Renta — Grandes Contribuyentes',
                'periodicity' => 'anual', 'nit_reference' => 'ultimo_digito', 'regime' => 'ordinario',
                'group_label' => 'Renta', 'sort_order' => 4,
                'custom_periods' => ['1a. Cuota (pago) — Febrero', '2a. Cuota (declaración y pago) — Abril', '3a. Cuota (pago) — Junio'],
            ],
            [
                'code' => 'PATRIMONIO', 'name' => 'Impuesto al Patrimonio',
                'periodicity' => 'anual', 'nit_reference' => 'ultimo_digito', 'regime' => 'ambos',
                'group_label' => 'Patrimonio', 'sort_order' => 13,
                'custom_periods' => ['1a. Cuota (declaración y pago) — Mayo', '2a. Cuota (pago) — Septiembre'],
            ],
            [
                'code' => 'CARBONO', 'name' => 'Impuesto al Carbono',
                'periodicity' => 'bimestral', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Otros impuestos nacionales', 'sort_order' => 14,
            ],
            [
                'code' => 'GASOLINA', 'name' => 'Gasolina y ACPM',
                'periodicity' => 'mensual', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Otros impuestos nacionales', 'sort_order' => 15,
            ],
            [
                'code' => 'PES_ANT', 'name' => 'Presencia Económica Significativa — Pago Anticipado',
                'periodicity' => 'bimestral', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Otros impuestos nacionales', 'sort_order' => 16,
            ],
            [
                'code' => 'PES_DEC', 'name' => 'Presencia Económica Significativa — Declaración Anual',
                'periodicity' => 'anual', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Otros impuestos nacionales', 'sort_order' => 17,
                'custom_periods' => ['Declaración anual — Abril'],
            ],
            [
                'code' => 'PLASTICOS', 'name' => 'Productos Plásticos de un Solo Uso',
                'periodicity' => 'anual', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Otros impuestos nacionales', 'sort_order' => 18,
                'custom_periods' => ['Presentación y pago — Febrero'],
            ],
            [
                'code' => 'BEBIDAS', 'name' => 'Bebidas Ultraprocesadas y Alimentos Ultraprocesados',
                'periodicity' => 'bimestral', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Otros impuestos nacionales', 'sort_order' => 19,
            ],
            [
                'code' => 'RUB', 'name' => 'Registro Único de Beneficiarios Finales',
                'periodicity' => 'trimestral', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Otros impuestos nacionales', 'sort_order' => 20,
                'custom_periods' => ['Actualización — Febrero', 'Actualización — Mayo', 'Actualización — Agosto', 'Actualización — Noviembre'],
            ],
            [
                'code' => 'PT_INFORME', 'name' => 'Precios de Transferencia — Declaración Informativa',
                'periodicity' => 'anual', 'nit_reference' => 'ultimo_digito', 'regime' => 'ambos',
                'group_label' => 'Precios de transferencia', 'sort_order' => 21,
                'custom_periods' => ['Declaración informativa — Septiembre'],
            ],
            [
                'code' => 'PT_DOC', 'name' => 'Precios de Transferencia — Documentación Comprobatoria',
                'periodicity' => 'anual', 'nit_reference' => 'ultimo_digito', 'regime' => 'ambos',
                'group_label' => 'Precios de transferencia', 'sort_order' => 22,
                'custom_periods' => ['Informe local e informe maestro — Septiembre'],
            ],
            [
                'code' => 'PT_CBC', 'name' => 'Precios de Transferencia — Informe País por País',
                'periodicity' => 'anual', 'nit_reference' => 'fecha_fija', 'regime' => 'ambos',
                'group_label' => 'Precios de transferencia', 'sort_order' => 23,
                'custom_periods' => ['Informe país por país — Diciembre'],
            ],
            [
                'code' => 'ACTIVOS_EXT', 'name' => 'Activos en el Exterior',
                'periodicity' => 'anual', 'nit_reference' => 'dos_ultimos_digitos', 'regime' => 'ambos',
                'group_label' => 'Internacional', 'sort_order' => 24,
                'custom_periods' => ['Declaración anual'],
                'description' => 'Sin fechas propias: hereda el vencimiento de la obligación de Renta o RST que tenga el cliente.',
            ],
        ];

        foreach ($obligations as $ob) {
            $ob['custom_periods'] = isset($ob['custom_periods']) ? json_encode($ob['custom_periods']) : null;
            DB::table('tax_obligation_types')->insert(array_merge([
                'description' => null,
                'responsibility_key' => null,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ], $ob));
        }
    }
};
