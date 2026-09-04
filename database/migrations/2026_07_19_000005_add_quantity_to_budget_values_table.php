<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_values', function (Blueprint $table) {
            // Solo usado en renglones de Ventas modo "unidades": `value` sigue
            // siendo el total en pesos (calculado = quantity × precio del período).
            $table->decimal('quantity', 18, 4)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('budget_values', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
