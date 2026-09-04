<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indicadores por año calendario (inflación, SMMLV, auxilio de transporte),
     * réplica de la tabla "Datos base" (filas 32-35) del caso práctico de
     * presupuestos de Actualícese. Filas libres por año en vez de columnas fijas,
     * para poder agregar años sin migraciones nuevas.
     */
    public function up(): void
    {
        Schema::create('client_budget_yearly_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->enum('indicator', ['inflacion', 'smmlv', 'auxilio_transporte']);
            $table->unsignedSmallInteger('year');
            $table->decimal('value', 18, 2);
            $table->timestamps();
            $table->unique(['client_id', 'indicator', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_budget_yearly_data');
    }
};
