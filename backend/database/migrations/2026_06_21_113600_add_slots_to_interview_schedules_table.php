<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->json('available_slots')->nullable()->after('scheduled_at');
            $table->dateTime('scheduled_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->dropColumn('available_slots');
            $table->dateTime('scheduled_at')->nullable(false)->change();
        });
    }
};
