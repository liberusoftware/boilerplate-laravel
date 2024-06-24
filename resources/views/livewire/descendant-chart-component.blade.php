<!--
File: descendant-chart.blade.php
Description: This file contains the descendant chart display and related functionality.
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descendant Chart</title>
    <script src="https://d3js.org/d3.v6.min.js"></script>
</head>
<body>
    <div id="descendant-chart-container"></div>

<x-filament::widget class="filament-descendant-chart-widget">
    <x-filament::card>
        <div id="descendant-chart-container"></div>
    </x-filament::card>

    @push('scripts')
        <script src="https://d3js.org/d3.v6.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const data = @json($descendantsData);

                /**
                 * Renders the descendant chart using the provided data.
                 *
                 * @param {Array} data - The data used to render the chart.
                 */
                function renderDescendantChart(data) {
                    const container = d3.select('#descendant-chart-container');
                    const width = 960;
                    const height = 500;

                    const svg = container.append('svg')
                        .attr('width', width)
                        .attr('height', height)
                        .attr('class', 'shadow-lg');

                    const nodes = svg.selectAll('circle')
                        .data(data)
                        .enter()
                        .append('g')
                        .attr('transform', (d, i) => `translate(${i * 100 + 50}, ${height / 2})`);

                    nodes.append('circle')
                        .attr('r', 40)
                        .attr('class', 'fill-current text-blue-500');

                    nodes.append('text')
                        .attr('dy', 5)
                        .text(d => d.name);
                }

                if (data && data.length > 0) {
                    renderDescendantChart(data);
                } else {
                    console.warn('No data available to render the descendant chart.');
                }
            });
        </script>
    @endpush
</x-filament::widget>
</body>
</html>



{{-- @livewire('descendant-chart-component')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descendant Chart</title>
    <script src="https://d3js.org/d3.v6.min.js"></script>
    @livewireStyles
</head>
<body>
    <div id="fanChartContainer"></div>
    <script src="{{ asset('js/fan-chart.js') }}"></script>
    @livewireScripts
</body>
</html> --}}
