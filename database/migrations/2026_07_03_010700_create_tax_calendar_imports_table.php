<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_calendar_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('original_name', 255);
            $table->string('path', 512);
            $table->enum('status', ['pending', 'reviewed', 'imported', 'failed'])->default('pending');
            $table->json('parsed_rows')->nullable();
            $table->json('summary')->nullable();
            $table->text('parse_notes')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_calendar_imports');
    }
};
