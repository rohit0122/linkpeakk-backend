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
        // Drop legacy tables
        Schema::dropIfExists('plan_changes');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('invoices');

        // Clean up plans table
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'razorpay_plan_id')) {
                $table->dropColumn('razorpay_plan_id');
            }
            if (Schema::hasColumn('plans', 'billing_interval')) {
                $table->dropColumn('billing_interval');
            }
            if (Schema::hasColumn('plans', 'trial_days')) {
                $table->dropColumn('trial_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-adding deleted columns to plans if needed
        Schema::table('plans', function (Blueprint $table) {
            $table->string('razorpay_plan_id')->nullable();
            $table->string('billing_interval')->default('month');
            $table->integer('trial_days')->default(0);
        });

        // Re-creating tables is complex and usually not needed for a one-way migration,
        // but for completeness one would define the schemas again here.
    }
};
