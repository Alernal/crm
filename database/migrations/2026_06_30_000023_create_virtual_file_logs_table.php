<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_file_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('virtual_file_id')->nullable()->constrained('virtual_files')->nullOnDelete();
            $table->string('filename');            // denormalizado para historial tras eliminar
            $table->enum('action', ['upload', 'download', 'move', 'rename', 'delete', 'restore']);
            $table->json('details')->nullable();   // before/after para move/rename
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_file_logs');
    }
};
