<x-filament::widget class="filament-pedigree-chart-widget">
    <x-filament::card>
        <div id="pedigree-chart-container">
            <!-- Chart will be rendered inside this div -->
        </div>
    </x-filament::card>

    @push('scripts')
        <script src="{{ asset('js/d3.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const peopleData = @json($people);
                if (peopleData && peopleData.length > 0) {
                    initializePedigreeChart(peopleData);
                } else {
                    console.warn('No data available to render the pedigree chart.');
                }

                function initializePedigreeChart(peopleData) {
                    const container = d3.select('#pedigree-chart-container');
                    const width = 800;
                    const height = 600;

                    const svg = container.append('svg')
                        .attr('width', width)
                        .attr('height', height);

                    // Render pedigree chart using peopleData
                    // ...
                }

                @this.on('zoomIn', () => {
                    // Implement zoom in logic
                    // ...
                });

                @this.on('zoomOut', () => {
                    // Implement zoom out logic
                    // ...
                });

                @this.on('pan', direction => {
                    // Implement panning logic based on direction
                    // ...
                });
            });
        </script>
    @endpush

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/pedigree-chart.css') }}">
    @endpush
</x-filament::widget>
