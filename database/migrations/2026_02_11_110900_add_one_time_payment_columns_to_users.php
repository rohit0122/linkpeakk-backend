<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $col) {
            $col->foreignId('plan_id')->nullable()->after('role')->constrained('plans')->onDelete('set null');
            $col->timestamp('plan_expires_at')->nullable()->after('plan_id');
            $col->foreignId('pending_plan_id')->nullable()->after('plan_expires_at')->constrained('plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $col) {
            $col->dropForeign(['plan_id']);
            $col->dropColumn('plan_id');
            $col->dropColumn('plan_expires_at');
            $col->dropForeign(['pending_plan_id']);
            $col->dropColumn('pending_plan_id');
        });
    }
};
