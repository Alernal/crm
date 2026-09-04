<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Copia editable "en construcción" de una plantilla: qué clause_block usa,
 * en qué orden, con qué texto propio y configuración. Al guardar la
 * plantilla se congela un snapshot inmutable en document_template_versions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->foreignId('clause_block_id')->constrained('clause_blocks')->restrictOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('title_override')->nullable();
            $table->longText('content_override')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_clauses');
    }
};
