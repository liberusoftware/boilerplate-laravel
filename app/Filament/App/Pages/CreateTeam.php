<?php

namespace App\Filament\App\Pages;

use Filament\Support\Enums\Width;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateTeam extends RegisterTenant
{
    protected string $view = 'filament.pages.create-team';

    public $name = '';

    protected Width|string|null $maxWidth = '2xl';

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

    protected function handleRegistration(array $data): Model
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
