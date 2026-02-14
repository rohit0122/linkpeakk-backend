<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Seed Users
        $commonPassword = Hash::make('password');

        $planMap = DB::table('plans')->pluck('id', 'slug')->toArray();

        $testUsers = [
            [
                'name' => 'Free User',
                'email' => 'free@yopmail.com',
                'password' => $commonPassword,
                'role' => 'user',
                'plan_id' => $planMap['free'] ?? null,
                'plan_expires_at' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro User',
                'email' => 'pro@yopmail.com',
                'password' => $commonPassword,
                'role' => 'user',
                'plan_id' => $planMap['pro'] ?? null,
                'plan_expires_at' => now()->addDays(30),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agency User',
                'email' => 'agency@yopmail.com',
                'password' => $commonPassword,
                'role' => 'agency',
                'plan_id' => $planMap['agency'] ?? null,
                'plan_expires_at' => now()->addDays(30),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Demo User',
                'email' => 'demo@yopmail.com',
                'password' => $commonPassword,
                'role' => 'user',
                'plan_id' => $planMap['demo'] ?? null,
                'plan_expires_at' => now()->addDays(7),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro to Agency Simulation',
                'email' => 'pro_to_agency@yopmail.com',
                'password' => $commonPassword,
                'role' => 'user',
                'plan_id' => $planMap['pro'] ?? null,
                'plan_expires_at' => now()->addDays(30),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agency to Pro Simulation',
                'email' => 'agency_to_pro@yopmail.com',
                'password' => $commonPassword,
                'role' => 'agency',
                'plan_id' => $planMap['agency'] ?? null,
                'plan_expires_at' => now()->addDays(30),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($testUsers as $user) {
            if (DB::table('users')->where('email', $user['email'])->doesntExist()) {
                DB::table('users')->insert($user);
            }
        }

        // 2. Simulate Plan Changes (Pending State)
        // Scenario: Pro user has already requested to downgrade to Free after current plan ends
        if (isset($planMap['free'])) {
            DB::table('users')->where('email', 'pro@yopmail.com')->update([
                'pending_plan_id' => $planMap['free'],
            ]);
        }

        // Scenario: Pro to Agency (Upgrade Simulation)
        if (isset($planMap['agency'])) {
            DB::table('users')->where('email', 'pro_to_agency@yopmail.com')->update([
                'pending_plan_id' => $planMap['agency'],
            ]);
        }

        // Scenario: Agency to Pro (Downgrade Simulation)
        if (isset($planMap['pro'])) {
            DB::table('users')->where('email', 'agency_to_pro@yopmail.com')->update([
                'pending_plan_id' => $planMap['pro'],
            ]);
        }

        // Scenario: Agency user is in a "renewal window" (e.g. expires in 5 days)
        DB::table('users')->where('email', 'agency@yopmail.com')->update([
            'plan_expires_at' => now()->addDays(5),
        ]);

        echo "Manual test users and simulation scenarios seeded successfully.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $emails = [
            'free@yopmail.com',
            'pro@yopmail.com',
            'agency@yopmail.com',
            'demo@yopmail.com',
        ];

        DB::table('users')->whereIn('email', $emails)->delete();
    }
};
