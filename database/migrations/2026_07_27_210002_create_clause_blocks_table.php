<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo global de "tipos de cláusula" que el ClauseEngine sabe resolver.
 * Define CÓMO se resuelve una cláusula (estrategia), no el texto final de
 * una plantilla concreta — eso vive en template_clauses.content_override.
 *
 * resolver_strategy = 'static'   -> PlaceholderEngine sobre default_content/content_override, sin lógica adicional.
 * resolver_strategy = 'computed' -> una clase ClauseResolver arma el texto desde datos calculados (fechas, pago).
 * resolver_strategy = 'builder'  -> una clase ClauseResolver arma el texto desde una colección (servicios seleccionados).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clause_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->enum('resolver_strategy', ['static', 'computed', 'builder'])->default('static');
            $table->string('resolver_class')->nullable();
            $table->string('default_title');
            $table->text('default_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clause_blocks');
    }
};
