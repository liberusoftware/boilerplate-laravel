<?php

namespace App\Filament\Admin\Resources\LanguageSettingsResource\Pages;

use App\Filament\Admin\Resources\LanguageSettingsResource;
use App\Services\TranslationService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class ManageLanguageSettings extends Page
{
    protected static string $resource = LanguageSettingsResource::class;

    protected string $view = 'filament.admin.resources.language-settings-resource.pages.manage-language-settings';

    public $supportedLanguages = [];

    public function mount(): void
    {
        $translationService = app(TranslationService::class);
        $this->supportedLanguages = $translationService->getSupportedLanguages();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateTranslations')
                ->label('Generate Translations')
                ->icon('heroicon-o-language')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('source_language')
                        ->label('Source Language')
                        ->options([
                            'en' => 'English',
                            'es' => 'Spanish',
                            'fr' => 'French',
                            'de' => 'German',
                        ])
                        ->default('en')
                        ->required(),
                    Forms\Components\Select::make('target_language')
                        ->label('Target Language (optional - leave empty for all)')
                        ->options([
                            'es' => 'Spanish',
                            'fr' => 'French',
                            'de' => 'German',
                        ])
                        ->placeholder('All languages'),
                    Forms\Components\Toggle::make('force')
                        ->label('Overwrite existing translations')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $options = [
                        '--source' => $data['source_language'],
                    ];

                    if (!empty($data['target_language'])) {
                        $options['--target'] = $data['target_language'];
                    }

                    if ($data['force']) {
                        $options['--force'] = true;
                    }

                    try {
                        Artisan::call('translate:generate', $options);

                        Notification::make()
                            ->title('Translations generated successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error generating translations')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
