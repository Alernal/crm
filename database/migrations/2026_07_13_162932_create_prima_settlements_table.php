<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prima_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('semester');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date');
            $table->timestamps();

            $table->unique(['client_id', 'year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prima_settlements');
    }
};
