<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El estado "Proyectado" desaparece: el submódulo Presupuestos (Flujo de
     * Caja) ahora se proyecta automáticamente al crear/editar/ver, sin un
     * estado intermedio propio. Los registros existentes con ese valor
     * (tanto Presupuestos como Estados Financieros, que comparten la misma
     * columna) pasan a "Borrador" — nunca significó "aprobado".
     */
    public function up(): void
    {
        DB::table('budgets')->where('status', 'projected')->update(['status' => 'draft']);
    }

    public function down(): void
    {
        // Irreversible a propósito: no hay forma de distinguir qué registros
        // eran "projected" antes de esta migración.
    }
};
