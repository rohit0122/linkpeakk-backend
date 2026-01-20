<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_url_blob');
        });

        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn('profile_image_blob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('avatar_url_blob')->nullable();
        });

        Schema::table('bio_pages', function (Blueprint $table) {
            $table->longText('profile_image_blob')->nullable();
        });
    }
};
