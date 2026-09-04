<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PP&E de la inversión inicial de un presupuesto de flujo de caja
     * (modal "Inversión inicial y PP&E"). Alimenta automáticamente la
     * depreciación y el monto de "Compra de propiedad, planta y equipo".
     */
    public function up(): void
    {
        Schema::create('budget_investment_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('value', 18, 2);
            $table->unsignedTinyInteger('useful_life_years');
            $table->unsignedTinyInteger('purchase_period_index')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_investment_assets');
    }
};
