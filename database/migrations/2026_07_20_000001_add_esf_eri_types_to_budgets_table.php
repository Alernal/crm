<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->enum('type', ['ventas', 'flujo_caja', 'nomina', 'esf', 'eri'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->enum('type', ['ventas', 'flujo_caja', 'nomina'])->change();
        });
    }
};
