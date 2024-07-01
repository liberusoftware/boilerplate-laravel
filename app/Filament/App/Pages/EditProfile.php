<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class EditProfile extends Page
{
    protected static string $view = 'filament.pages.edit-profile';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public User $user;

    public function mount()
    {
        $this->user = Auth::user();
        $this->form->fill([
            'name'  => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email Address')
                ->required()
                ->maxLength(255),
        ];
    }

    public function submit()
    {
        $this->validate();

        $state = $this->form->getState();

        $this->user->forceFill([
            'name'  => $state['name'],
            'email' => $state['email'],
        ])->save();

        Filament::notify('success', 'Your profile has been updated.');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->current() => 'Edit Profile',
        ];
    }
}
