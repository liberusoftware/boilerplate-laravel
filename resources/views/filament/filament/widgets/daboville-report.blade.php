<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daboville Report</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div id="descendant-chart-container" class="flex justify-center items-center w-full h-screen bg-gray-100 overflow-auto">
        <!-- Chart will be rendered inside this div by descendant-chart.js -->
    </div>
    <script src="{{ asset('js/filament/widgets/components/descendant-chart.js') }}"></script>
</body>
</html>