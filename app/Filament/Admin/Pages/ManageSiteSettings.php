<?php

namespace App\Filament\Admin\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Settings\SiteSettings;
use Filament\Forms;
use Filament\Pages\SettingsPage;

class ManageSiteSettings extends SettingsPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = SiteSettings::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?string $title = 'Site Settings';

    protected static ?string $navigationLabel = 'Site Settings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Site Information')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('site_email')
                            ->label('Site Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('site_phone')
                            ->label('Site Phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('site_address')
                            ->label('Site Address')
                            ->maxLength(255),
                        TextInput::make('site_country')
                            ->label('Country')
                            ->maxLength(255),
                        TextInput::make('site_currency')
                            ->label('Currency Symbol')
                            ->maxLength(10),
                        TextInput::make('site_default_language')
                            ->label('Default Language')
                            ->maxLength(10)
                            ->default('en'),
                    ])
                    ->columns(2),

                Section::make('Social Media Links')
                    ->description('Add your social media profile URLs')
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('twitter_url')
                            ->label('Twitter URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('github_url')
                            ->label('GitHub URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Footer')
                    ->schema([
                        Textarea::make('footer_copyright')
                            ->label('Copyright Text')
                            ->required()
                            ->maxLength(500)
                            ->rows(2),
                    ]),
            ]);
    }
}