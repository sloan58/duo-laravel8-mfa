<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::create([
            'name' => 'User with MFA Domain',
            'email'=> 'user@withmfa.com',
            'password' => \Hash::make('secret')
        ]);

        \App\Models\User::create([
            'name' => 'User Without MFA Domain',
            'email'=> 'user@withoutmfa.com',
            'password' => \Hash::make('secret')
        ]);
    }
}
