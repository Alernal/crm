<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nit')->nullable()->after('name');
            $table->string('phone', 20)->nullable()->after('nit');
            $table->string('city', 100)->nullable()->after('phone');
            $table->string('address')->nullable()->after('city');
            $table->string('professional_card_number', 50)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nit', 'phone', 'city', 'address', 'professional_card_number']);
        });
    }
};
