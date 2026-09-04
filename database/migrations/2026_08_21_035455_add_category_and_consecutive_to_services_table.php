<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')
                ->constrained('service_categories')->nullOnDelete();
            $table->unsignedInteger('consecutive_number')->nullable()->after('category_id');
        });

        DB::table('services')
            ->select('id', 'user_id')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id')
            ->each(function ($services, $userId) {
                $number = 0;
                foreach ($services as $service) {
                    $number++;
                    DB::table('services')->where('id', $service->id)->update(['consecutive_number' => $number]);
                }
                DB::table('users')->where('id', $userId)->update(['service_consecutive' => $number]);
            });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('consecutive_number');
        });
    }
};
