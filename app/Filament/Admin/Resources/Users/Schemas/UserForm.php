<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required(),

                        TextInput::make('password')
                            ->password(),


                        FileUpload::make('profile_photo_path')
                            ->label("Profile Photo")
                            ->image()
                    ])
            ]);
    }
}
