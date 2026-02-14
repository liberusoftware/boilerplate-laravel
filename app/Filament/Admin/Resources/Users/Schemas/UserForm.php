<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('User Management')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Basic Information')
                            ->schema([
                                Section::make('Personal Details')
                                    ->description('Basic user information and profile settings')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull()
                                            ->placeholder('Enter full name'),
                                        
                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->placeholder('user@example.com'),

                                        TextInput::make('password')
                                            ->password()
                                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->required(fn (string $context): bool => $context === 'create')
                                            ->maxLength(255)
                                            ->placeholder('Enter password')
                                            ->helperText('Leave blank to keep current password (when editing)'),
                                        
                                        FileUpload::make('profile_photo_path')
                                            ->label('Profile Photo')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->directory('profile-photos')
                                            ->columnSpanFull()
                                            ->helperText('Upload a profile photo (max 2MB)')
                                    ]),
                            ]),
                        
                        Tab::make('Roles & Permissions')
                            ->schema([
                                Section::make('Role Assignment')
                                    ->description('Assign roles to control user access and permissions')
                                    ->schema([
                                        Select::make('roles')
                                            ->relationship('roles', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->placeholder('Select roles')
                                            ->helperText('Users inherit all permissions from their assigned roles')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        
                        Tab::make('Account Settings')
                            ->schema([
                                Section::make('Account Status')
                                    ->description('Manage account verification and security settings')
                                    ->columns(2)
                                    ->schema([
                                        DateTimePicker::make('email_verified_at')
                                            ->label('Email Verified At')
                                            ->placeholder('Not verified')
                                            ->helperText('Mark as verified to enable full account access')
                                            ->native(false),
                                        
                                        TextInput::make('current_team_id')
                                            ->label('Current Team ID')
                                            ->numeric()
                                            ->placeholder('Default team')
                                            ->helperText('The user\'s active team context'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
