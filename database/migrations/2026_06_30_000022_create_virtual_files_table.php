<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('virtual_folders')->nullOnDelete();
            $table->string('original_filename');
            $table->string('storage_filename');   // UUID.ext en disco
            $table->string('file_path');           // path relativo en storage
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');  // bytes
            $table->softDeletes();                 // papelera (30 días)
            $table->timestamps();

            $table->index(['user_id', 'client_id']);
            $table->index('folder_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_files');
    }
};
