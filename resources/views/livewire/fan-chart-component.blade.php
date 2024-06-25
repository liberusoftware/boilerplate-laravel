<x-filament::widget class="filament-fan-chart-widget">
    <x-filament::card>
        <div id="fanChartContainer"></div>
    </x-filament::card>

    @push('scripts')
        <script src="https://d3js.org/d3.v6.min.js"></script>
        <script src="{{ asset('js/fan-chart.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const people = @json($people);
                if (people && people.length > 0) {
                    initializeFanChart(people);
                } else {
                    console.warn('No data available to render the fan chart.');
                }
            });
        </script>
    @endpush
</x-filament::widget>
