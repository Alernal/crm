<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo global (no aislado por user_id) de tipos de documento que el
 * Motor Documental sabe generar. Puramente taxonómico — la lógica de
 * cada tipo vive en las plantillas y en clause_blocks, no aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon', 50)->default('file-text');
            $table->string('default_prefix', 10);
            $table->boolean('requires_dual_signature')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
