<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $adminPassword = Str::random(12);
        $staffPassword = Str::random(12);

        // Print passwords to console
        echo "Admin password: {$adminPassword}\n";
        echo "Staff password: {$staffPassword}\n";

        // Here you can save these passwords to your user creation logic as needed
    }
}