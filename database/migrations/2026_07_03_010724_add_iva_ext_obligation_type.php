<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tax_obligation_types')->insert([
            'code'           => 'IVA_EXT',
            'name'           => 'IVA — Prestadores de Servicios desde el Exterior',
            'periodicity'    => 'bimestral',
            'nit_reference'  => 'fecha_fija',
            'regime'         => 'ambos',
            'group_label'    => 'IVA',
            'sort_order'     => 2,
            'active'         => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('tax_obligation_types')->where('code', 'IVA_EXT')->delete();
    }
};
