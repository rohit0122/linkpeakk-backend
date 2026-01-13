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
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('link_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['view', 'click', 'like']);
            $table->date('date');
            $table->unsignedBigInteger('count')->default(0);
            $table->unsignedBigInteger('unique_count')->default(0);
            $table->timestamps();

            // Performance Indexes
            $table->index(['bio_page_id', 'date', 'type']);
            $table->index(['link_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
