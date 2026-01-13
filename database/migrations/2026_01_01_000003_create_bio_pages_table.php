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
        Schema::create('bio_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('template')->default('classic');
            $table->string('theme')->default('classic');
            $table->string('profile_image')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('unique_views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->json('seo')->nullable();
            $table->json('social_links')->nullable();
            $table->json('branding')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('show_branding')->default(true);
            $table->timestamps();

            // Performance Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bio_pages');
    }
};
