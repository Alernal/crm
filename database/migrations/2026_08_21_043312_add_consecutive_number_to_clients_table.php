<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedInteger('consecutive_number')->nullable()->after('user_id');
        });

        DB::table('clients')
            ->select('id', 'user_id')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id')
            ->each(function ($clients, $userId) {
                $number = 0;
                foreach ($clients as $client) {
                    $number++;
                    DB::table('clients')->where('id', $client->id)->update(['consecutive_number' => $number]);
                }
                DB::table('users')->where('id', $userId)->update(['client_consecutive' => $number]);
            });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('consecutive_number');
        });
    }
};
