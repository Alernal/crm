<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuando el cliente es persona jurídica, quien firma el contrato es el
 * representante legal, no la empresa en sí — el Motor Documental necesita
 * estos datos para redactar el preámbulo correctamente (ver
 * PreambleClauseResolver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('legal_representative_name')->nullable()->after('contact_person');
            $table->enum('legal_representative_document_type', ['CC', 'CE', 'Pasaporte'])->nullable()->after('legal_representative_name');
            $table->string('legal_representative_document_number', 20)->nullable()->after('legal_representative_document_type');
            $table->string('chamber_of_commerce_city', 100)->nullable()->after('legal_representative_document_number');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'legal_representative_name',
                'legal_representative_document_type',
                'legal_representative_document_number',
                'chamber_of_commerce_city',
            ]);
        });
    }
};
