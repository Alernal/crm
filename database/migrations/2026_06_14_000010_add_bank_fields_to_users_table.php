<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('logo_path');
            $table->enum('account_type', ['savings', 'checking'])->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_type');
            $table->string('account_holder_name')->nullable()->after('account_number');
            $table->string('account_holder_id')->nullable()->after('account_holder_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_type', 'account_number', 'account_holder_name', 'account_holder_id']);
        });
    }
};
