<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LanguageSettingsResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LanguageSettingsResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Languages';

    protected static ?int $navigationSort = 10;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLanguageSettings::route('/'),
        ];
    }
}
