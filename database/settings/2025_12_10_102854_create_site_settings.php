<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.site_name', config('app.name', 'Liberu'));
        $this->migrator->add('site.site_email', 'info@example.com');
        $this->migrator->add('site.site_phone', '');
        $this->migrator->add('site.site_address', '');
        $this->migrator->add('site.site_country', '');
        $this->migrator->add('site.site_currency', '$');
        $this->migrator->add('site.site_default_language', 'en');
        $this->migrator->add('site.facebook_url', null);
        $this->migrator->add('site.twitter_url', null);
        $this->migrator->add('site.github_url', 'https://github.com/liberusoftware/boilerplate-laravel');
        $this->migrator->add('site.youtube_url', null);
        $this->migrator->add('site.footer_copyright', '© ' . date('Y') . ' ' . config('app.name', 'Liberu') . '. All rights reserved.');
    }
};
