<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rol de la sección dentro de un Estado de Situación Financiera o Estado
     * de Resultados Integral (activo_corriente, costo_ventas, etc. — ver
     * Budget::ESF_SECTION_ROLES / ERI_SECTION_ROLES). Permite que el usuario
     * renombre la sección libremente sin romper el cálculo de subtotales,
     * a diferencia de `is_outflow` en Flujo de Caja que depende de posición.
     */
    public function up(): void
    {
        Schema::table('budget_sections', function (Blueprint $table) {
            $table->string('statement_role')->nullable()->after('is_outflow');
        });
    }

    public function down(): void
    {
        Schema::table('budget_sections', function (Blueprint $table) {
            $table->dropColumn('statement_role');
        });
    }
};
