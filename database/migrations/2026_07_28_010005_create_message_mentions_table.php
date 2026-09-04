<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->morphs('mentioned');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'mentioned_type', 'mentioned_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_mentions');
    }
};
