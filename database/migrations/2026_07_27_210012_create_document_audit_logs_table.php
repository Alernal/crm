<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de eventos por documento — mismo patrón ya usado por
 * VirtualFileLog en el módulo de Archivo en la Nube (tabla dedicada por
 * entidad en vez de un paquete de auditoría genérico nuevo).
 */
return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('document_audit_logs');
    }
};
