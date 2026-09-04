<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial legal, inmutable y append-only de un generated_document. Cada
 * guardado del editor crea una fila nueva — nunca se actualiza una
 * existente. "Restaurar una versión anterior" crea una versión nueva
 * copiando el contenido de la vieja (el historial nunca retrocede
 * destructivamente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('generated_documents')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('clauses_data');
            $table->longText('content_html');
            $table->string('change_summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['document_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
