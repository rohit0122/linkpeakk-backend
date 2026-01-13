<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $email = 'rohit.seed@gmail.com';
        
        if (User::where('email', $email)->exists()) {
            echo "User with email {$email} already exists.\n";
            return;
        }

        $password = '$Holishit22';

        User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make($password), // Hashed password
            'role' => 'admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        echo "\n";
        echo "--------------------------------------------------\n";
        echo "Admin User Created Successfully!\n";
        echo "Email: {$email}\n";
        echo "Password: {$password}\n";
        echo "--------------------------------------------------\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('email', 'rohit.seed@gmail.com')->delete();
    }
};
