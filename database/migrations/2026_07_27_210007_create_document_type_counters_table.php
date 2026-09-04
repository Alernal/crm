<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numeración atómica por usuario + tipo de documento + año. Mismo patrón
 * ya usado por clients.invoice_consecutive (lockForUpdate()+increment()
 * en DocumentNumberingService), pero desacoplado del cliente porque la
 * numeración de contratos es consecutiva dentro de toda la firma, no por
 * cliente individual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('prefix', 10);
            $table->unsignedInteger('consecutive')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'document_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_counters');
    }
};
