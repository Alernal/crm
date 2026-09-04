<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot inmutable de una plantilla en un momento dado — nunca se
 * actualiza, solo se crean filas nuevas (historial de plantillas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('clauses_snapshot');
            $table->string('change_summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['template_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_versions');
    }
};
