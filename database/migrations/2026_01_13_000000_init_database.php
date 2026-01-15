<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            // Added columns from separate migrations
            $table->longText('avatar_url_blob')->nullable();
            $table->string('verification_token')->nullable();
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['role', 'is_active']);
        });

        // 2. Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('razorpay_plan_id')->nullable();
            $table->decimal('price', 8, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval')->default('month');
            $table->integer('trial_days')->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed Plans Data
        $plans = [
            [
                'name' => 'DEMO',
                'slug' => 'demo',
                'razorpay_plan_id' => null,
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 0,
                'is_active' => true,
                'features' => json_encode([
                    'links' => 5,
                    'pages' => 1,
                    'allowedTemplates' => ['classic'],
                    'themes' => 'ALL',
                    'analytics' => 7,
                    'customQR' => false,
                    'seo' => false,
                    'removeWatermark' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'FREE',
                'slug' => 'free',
                'razorpay_plan_id' => null,
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 0,
                'is_active' => true,
                'features' => json_encode([
                    'links' => 5,
                    'pages' => 1,
                    'allowedTemplates' => ['classic'],
                    'themes' => ['light', 'dark'],
                    'analytics' => 7,
                    'customQR' => false,
                    'seo' => false,
                    'removeWatermark' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'PRO',
                'slug' => 'pro',
                'razorpay_plan_id' => 'plan_S3Si6GlKBSnioK',
                'price' => 9.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 7,
                'is_active' => true,
                'features' => json_encode([
                    'links' => 1000,
                    'pages' => 1,
                    'allowedTemplates' => ['classic', 'bento', 'hero', 'influencer', 'sleek'],
                    'themes' => ['light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate', 'retro', 'cyberpunk', 'valentine', 'coffee'],
                    'analytics' => 90,
                    'customQR' => true,
                    'seo' => true,
                    'removeWatermark' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'AGENCY',
                'slug' => 'agency',
                'razorpay_plan_id' => 'plan_S3SieAJbKmSdPh',
                'price' => 49.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 7,
                'is_active' => true,
                'features' => json_encode([
                    'links' => 1000,
                    'pages' => 10,
                    'allowedTemplates' => 'ALL',
                    'themes' => 'ALL',
                    'analytics' => 9999,
                    'customQR' => true,
                    'seo' => true,
                    'removeWatermark' => true,
                    'customBranding' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('plans')->insert($plans);

        // 3. Bio Pages Table
        Schema::create('bio_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('template')->default('classic');
            $table->string('theme')->default('classic');
            $table->string('profile_image')->nullable();
            // Added column
            $table->longText('profile_image_blob')->nullable();
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

            $table->index('is_active');
        });

        // Ensure profile_image_blob is LONGBLOB for raw binary storage
        DB::statement('ALTER TABLE bio_pages MODIFY profile_image_blob LONGBLOB');

        // 4. Links Table
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bio_page_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('unique_clicks')->default(0);
            $table->timestamps();

            $table->index(['bio_page_id', 'is_active', 'order']);
        });

        // 5. Subscriptions Table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('razorpay_subscription_id')->nullable()->unique();
            $table->string('razorpay_customer_id')->nullable();
            $table->string('status');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'current_period_end']);
        });

        // 6. Plan Changes Table
        Schema::create('plan_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_plan_id')->constrained('plans')->onDelete('cascade');
            $table->foreignId('to_plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // 7. Analytics Table
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('link_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['view', 'click', 'like']);
            $table->date('date');
            $table->unsignedBigInteger('count')->default(0);
            $table->unsignedBigInteger('unique_count')->default(0);
            $table->timestamps();

            $table->index(['bio_page_id', 'date', 'type']);
            $table->index(['link_id', 'date']);
        });

        // 8. Tickets Table
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subject');
            $table->text('message');
            // Consolidated status enum
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            // Added category
            $table->string('category')->nullable()->default('General');
            $table->timestamps();
        });

        // 9. Leads Table
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('email');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['bio_page_id', 'created_at']);
        });

        // 10. Webhook Logs Table
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('provider')->default('razorpay');
            $table->string('external_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // 11. Cache Tables
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // 12. Queue Tables
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // 13. Sessions Table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // 14. Personal Access Tokens
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('analytics');
        Schema::dropIfExists('plan_changes');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('links');
        Schema::dropIfExists('bio_pages');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('users');
    }
};
