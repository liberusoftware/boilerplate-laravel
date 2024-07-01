<?php

namespace App\Filament\App\Pages;

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Facades\Auth;

class CreateTeam extends RegisterTenant
{
    protected static string $view = 'filament.pages.create-team';

    public $name = '';

    protected ?string $maxWidth = '2xl';

    public function mount(): void
    {
        // abort_unless(Filament::auth()->user()->canCreateTeams(), 403);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Team Name')
                ->required()
                ->maxLength(255),
        ];
    }

    protected function handleRegistration(array $data): Team
    {
        return app(\App\Actions\Jetstream\CreateTeam::class)->create(auth()->user(), $data);
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->current() => 'Create Team',
        ];
    }

    public static function getLabel(): string
    {
        return 'Create Team';
    }
}
