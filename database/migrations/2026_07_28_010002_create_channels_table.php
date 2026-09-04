<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->morphs('created_by');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['owner_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
