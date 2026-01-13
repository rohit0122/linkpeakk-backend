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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('provider')->default('razorpay');
            $table->string('external_id')->nullable()->index(); // e.g. razorpay_event_id
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('pending'); // pending, processed, failed
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
