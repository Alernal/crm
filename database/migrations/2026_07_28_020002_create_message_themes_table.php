<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_collapsed')->default(true);
            $table->morphs('created_by');
            $table->timestamps();

            $table->index(['channel_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_themes');
    }
};
