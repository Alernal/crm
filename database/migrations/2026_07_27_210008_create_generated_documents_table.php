<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un contrato/propuesta real generado para un cliente. Nombre distinto a
 * la tabla `documents` ya existente (uploader de cédula/tarjeta
 * profesional, sin relación funcional con este motor) para evitar
 * colisión de nombres.
 *
 * current_version_id no lleva FK aquí porque document_versions todavía no
 * existe en este punto — se agrega en
 * 2026_07_27_210010_add_current_version_foreign_to_generated_documents_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('number', 20);
            $table->unsignedSmallInteger('year');
            $table->string('full_number', 40);
            $table->enum('status', [
                'borrador', 'en_edicion', 'pendiente_revision', 'revisado',
                'aprobado', 'firmado', 'finalizado', 'anulado',
            ])->default('borrador');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_favorite')->default(false);
            $table->json('client_snapshot');
            $table->json('contractor_snapshot');
            $table->json('variables');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'document_type_id', 'full_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
