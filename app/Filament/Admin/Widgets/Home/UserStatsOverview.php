<?php

namespace App\Filament\Admin\Widgets\Home;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = User::whereNull('email_verified_at')->count();
        
        $lastMonthUsers = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        
        $growth = $lastMonthUsers > 0 
            ? round((($newUsersThisMonth - $lastMonthUsers) / $lastMonthUsers) * 100, 1)
            : 0;

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('All registered users')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart([7, 12, 18, 15, 20, 25, $totalUsers]),
            
            Stat::make('New This Month', $newUsersThisMonth)
                ->description($growth >= 0 ? "{$growth}% increase" : "{$growth}% decrease")
                ->descriptionIcon($growth >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($growth >= 0 ? 'success' : 'danger'),
            
            Stat::make('Verified Users', $verifiedUsers)
                ->description($unverifiedUsers . ' pending verification')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success')
                ->chart([
                    $verifiedUsers * 0.7,
                    $verifiedUsers * 0.8,
                    $verifiedUsers * 0.9,
                    $verifiedUsers
                ]),
        ];
    }
}
