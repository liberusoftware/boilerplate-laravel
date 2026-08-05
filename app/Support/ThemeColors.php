<?php

namespace App\Support;

use Filament\Support\Colors\Color;
use Liberu\Foundation\Theme\Services\ThemeManager;

final class ThemeColors
{
    public function __construct(private readonly ThemeManager $themes) {}

    public function forSite(): array
    {
        $colors = [
            'slate' => Color::Slate, 'gray' => Color::Gray, 'zinc' => Color::Zinc, 'neutral' => Color::Neutral,
            'stone' => Color::Stone, 'red' => Color::Red, 'orange' => Color::Orange, 'amber' => Color::Amber,
            'yellow' => Color::Yellow, 'lime' => Color::Lime, 'green' => Color::Green, 'emerald' => Color::Emerald,
            'teal' => Color::Teal, 'cyan' => Color::Cyan, 'sky' => Color::Sky, 'blue' => Color::Blue,
            'indigo' => Color::Indigo, 'violet' => Color::Violet, 'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia, 'pink' => Color::Pink, 'rose' => Color::Rose,
        ];

        return ['primary' => $colors[$this->themes->primaryColor($this->themes->getSiteTheme())] ?? Color::Amber];
    }
}
