<?php

namespace App\Filament\Admin\Widgets\Home;

use App\Models\Role;
use Filament\Widgets\ChartWidget;

class UsersByRoleChart extends ChartWidget
{
    protected static ?string $heading = 'Users by Role';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $roles = Role::withCount('users')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Users per Role',
                    'data' => $roles->pluck('users_count')->toArray(),
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)',
                    ],
                ],
            ],
            'labels' => $roles->pluck('name')->map(fn ($name) => ucfirst($name))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
