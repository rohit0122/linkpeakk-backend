<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('category')->nullable()->default('General')->after('priority');
            // We can't easily update enum values for status here if the driver is not MariaDB/MySQL with certain versions
            // but for WAMP it should be fine to drop and recreate or use raw SQL.
            // Using raw SQL for enum modification is safer on MySQL/MariaDB.
        });

        // Add 'resolved' to status enum
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'pending', 'resolved', 'closed') DEFAULT 'open'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('category');
        });
        
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'pending', 'closed') DEFAULT 'open'");
        }
    }
};
