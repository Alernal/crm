<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->morphs('member');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['channel_id', 'member_type', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_members');
    }
};
