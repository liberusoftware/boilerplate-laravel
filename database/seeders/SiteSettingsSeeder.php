<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSettings;

class SiteSettingsSeeder extends Seeder
{
    public function run()
    {
        SiteSettings::create([
            'name' => config('app.name', 'Liberu Real Estate'),
            'currency' => '£',
            'default_language' => 'en',
            'address' => '123 Real Estate St, London, UK',
            'country' => 'United Kingdom',
            'email' => 'info@liberurealestate.com',
        ]);
    }
}