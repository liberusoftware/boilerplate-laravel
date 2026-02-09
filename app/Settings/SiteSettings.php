<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SiteSettings extends Settings
{
    public string $site_name;
    public string $site_email;
    public ?string $site_phone;
    public ?string $site_address;
    public ?string $site_country;
    public string $site_currency;
    public string $site_default_language;
    public ?string $facebook_url;
    public ?string $twitter_url;
    public ?string $github_url;
    public ?string $youtube_url;
    public string $footer_copyright;

    public static function group(): string
    {
        return 'site';
    }
}
