<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Settings\SiteSettings;

class SiteSettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = app(SiteSettings::class);
        $settings->site_name = config('app.name', 'Liberu Real Estate');
        $settings->currency = '£';
        $settings->default_language = 'en';
        $settings->address = '123 Real Estate St, London, UK';
        $settings->country = 'United Kingdom';
        $settings->email = 'info@liberurealestate.com';

        if (method_exists($settings, 'save')) {
            $settings->save();
        }
    }
}