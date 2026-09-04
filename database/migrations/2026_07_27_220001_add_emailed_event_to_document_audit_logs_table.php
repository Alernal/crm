<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el evento 'emailed' al enum de auditoría. SQLite emula ENUM vía
 * un CHECK constraint atado a la columna, y su DROP COLUMN nativo rechaza
 * columnas referenciadas por un CHECK — el patrón dropColumn()+re-add()
 * usado en 2026_06_14_000012_fix_payment_method_enum_add_bre_b falla aquí
 * por eso. Como esta tabla no tiene datos reales todavía (solo registros
 * de prueba de esta misma sesión de desarrollo), se recrea directamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('document_audit_logs');

        Schema::create('document_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('generated_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('event', [
                'created', 'updated', 'version_created', 'status_changed',
                'shared', 'downloaded_pdf', 'downloaded_docx', 'printed',
                'emailed', 'duplicated', 'restored_version', 'deleted',
            ]);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_audit_logs');

        Schema::create('document_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('generated_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('event', [
                'created', 'updated', 'version_created', 'status_changed',
                'shared', 'downloaded_pdf', 'downloaded_docx', 'printed',
                'duplicated', 'restored_version', 'deleted',
            ]);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['document_id', 'created_at']);
        });
    }
};
