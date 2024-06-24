<x-filament::page>
    <x-filament::widgets :widgets="[
        \App\Filament\Widgets\PedigreeChartWidget::class,
        \App\Filament\Widgets\FanChartWidget::class,
        \App\Filament\Widgets\DescendantChartWidget::class,
    ]">
    </x-filament::widgets>
</x-filament::page>
