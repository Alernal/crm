<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('virtual_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');             // materialized path: "/1/5/23/"
            $table->boolean('is_system')->default(false);  // auto-created, no eliminar desde UI
            $table->timestamps();

            $table->index(['user_id', 'client_id']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_folders');
    }
};
