<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'danielmoses849@gmail.com';
        $password = 'password12345';

        // Check if admin with this email exists
        $admin = Admin::where('email', $email)->first();

        if ($admin) {
            $admin->update([
                'password' => Hash::make($password),
            ]);
            $this->command->info("Admin record {$email} updated successfully.");
        } else {
            // Check if there is ANY admin and update it, OR create a new one
            $genericAdmin = Admin::first();
            if ($genericAdmin) {
                $genericAdmin->update([
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                $this->command->info("Existing admin record updated to {$email}.");
            } else {
                Admin::create([
                    'name' => 'System Admin',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'level' => 'super', // Assuming level is required
                ]);
                $this->command->info("New admin record {$email} created successfully.");
            }
        }
    }
}
