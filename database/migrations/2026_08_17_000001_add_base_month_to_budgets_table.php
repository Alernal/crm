<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mes de inicio (1-12) para presupuestos con periodicidad mensual —
     * antes siempre se asumía enero. Solo tiene efecto cuando
     * `period_type = 'monthly'`; el resto de periodicidades no anuales
     * (trimestral/semestral/cuatrimestral) siguen ancladas a enero.
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->unsignedTinyInteger('base_month')->default(1)->after('base_year');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('base_month');
        });
    }
};
