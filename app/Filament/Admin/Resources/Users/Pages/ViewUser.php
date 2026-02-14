<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User Profile')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('profile_photo_url')
                            ->label('Profile Photo')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                            ->columnSpanFull(),
                        
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        TextEntry::make('email')
                            ->label('Email Address')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                        
                        IconEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        
                        TextEntry::make('email_verified_at')
                            ->label('Verified At')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not verified'),
                    ]),
                
                Section::make('Roles & Permissions')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Assigned Roles')
                            ->badge()
                            ->color('success')
                            ->formatStateUsing(fn ($state) => ucfirst($state))
                            ->placeholder('No roles assigned')
                            ->columnSpanFull(),
                        
                        TextEntry::make('permissions.name')
                            ->label('Direct Permissions')
                            ->badge()
                            ->color('info')
                            ->placeholder('No direct permissions')
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Team Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('teams.name')
                            ->label('Teams')
                            ->badge()
                            ->color('primary')
                            ->placeholder('No teams')
                            ->columnSpanFull(),
                        
                        TextEntry::make('currentTeam.name')
                            ->label('Current Team')
                            ->placeholder('No current team'),
                        
                        TextEntry::make('ownedTeams.name')
                            ->label('Owned Teams')
                            ->badge()
                            ->color('warning')
                            ->placeholder('No owned teams'),
                    ]),
                
                Section::make('Account Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M d, Y H:i')
                            ->since()
                            ->icon('heroicon-o-clock'),
                        
                        TextEntry::make('two_factor_confirmed_at')
                            ->label('Two-Factor Enabled')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        
                        TextEntry::make('profile_photo_path')
                            ->label('Profile Photo Path')
                            ->placeholder('No custom photo')
                            ->limit(50),
                    ]),
            ]);
    }
}
