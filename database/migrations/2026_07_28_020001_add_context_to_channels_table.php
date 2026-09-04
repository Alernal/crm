<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->enum('channel_type', ['general', 'contextual'])->default('general')->after('owner_id');
            $table->string('context_type')->nullable()->after('channel_type');
            $table->unsignedBigInteger('context_id')->nullable()->after('context_type');

            $table->index(['context_type', 'context_id']);
            $table->unique(['owner_id', 'context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropUnique(['owner_id', 'context_type', 'context_id']);
            $table->dropIndex(['context_type', 'context_id']);
            $table->dropColumn(['channel_type', 'context_type', 'context_id']);
        });
    }
};
