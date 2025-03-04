<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cpd.pitc.com.pk'],
            [
                'name' => 'System Admin',
                'email' => 'admin@cpd.pitc.com.pk',
                'password' => Hash::make('secret'),
            ]
        );
    }
}
