<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('rems.admin_email');
        $password = config('rems.admin_password');
        if (! $email || ! $password) {
            $this->command?->warn('Admin skipped: configure REMS_ADMIN_EMAIL and REMS_ADMIN_PASSWORD (12+ chars).');
            return;
        }
        if (strlen($password) < 12 || str_contains($password,'SET_A_LONG')) {
            throw new RuntimeException('Set a unique non-placeholder admin password of at least 12 characters.');
        }
        $existing = User::where('email',$email)->first();
        if ($existing && ! $existing->hasRole('super_admin')) {
            throw new RuntimeException('Existing non-admin account uses REMS_ADMIN_EMAIL; refusing to elevate it automatically.');
        }
        $user = $existing ?? User::create([
            'name'=>'System Administrator','email'=>$email,'password'=>Hash::make($password),
        ]);
        $user->assignRole('super_admin');
        $this->command?->info('Admin account ready (password not displayed).');
    }
}
