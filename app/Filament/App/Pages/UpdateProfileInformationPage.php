<?php

namespace App\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformationPage extends Page
{
    protected string $view = 'filament.pages.profile.update-profile-information';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Profile';

    public function mount(): void
    {
        $this->form->fill([
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required(),
            TextInput::make('email')
                ->label('Email Address')
                ->email()
                ->required(),
        ]);
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        Auth::user()->forceFill(array_filter([
            'name' => $state['name'] ?? null,
            'email' => $state['email'] ?? null,
        ]))->save();

        Notification::make()
            ->title('Profile updated successfully.')
            ->success()
            ->send();
    }

    public function getHeading(): string
    {
        return static::$title;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
