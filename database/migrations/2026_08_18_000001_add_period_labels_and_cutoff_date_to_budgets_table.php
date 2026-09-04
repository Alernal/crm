<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estados Financieros con periodicidad distinta a anual (trimestral,
     * cuatrimestral, semestral, mensual): en vez de pedir un año editable
     * por período (como sí tiene sentido para anual, donde cada período ES
     * un año), cada período usa una etiqueta de texto libre editable
     * ("Mes 1", "Cuatrimestre 2"...) — `period_labels`, mismo patrón de
     * arreglo por índice que `period_years`.
     *
     * `cutoff_date` es la fecha de corte explícita que se muestra en el
     * encabezado "A corte" de los estados financieros — puramente para
     * mostrar la fecha exacta que el usuario eligió; si no se define, se
     * sigue calculando como antes (`Budget::periodEndDate()`).
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->json('period_labels')->nullable()->after('period_years');
            $table->date('cutoff_date')->nullable()->after('period_labels');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn(['period_labels', 'cutoff_date']);
        });
    }
};
