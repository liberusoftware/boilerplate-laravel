<x-filament-panels::page>
    <section aria-labelledby="module-heading" class="space-y-3">
        <h2 id="module-heading" class="text-lg font-semibold">Installed modules</h2>
        <div class="overflow-x-auto"><table class="w-full"><thead><tr><th scope="col">Module</th><th scope="col">Version</th><th scope="col">Capabilities</th></tr></thead><tbody>
        @foreach ($modules as $module)<tr><th scope="row">{{ $module['display_name'] }}</th><td>{{ $module['version'] }}</td><td>{{ implode(', ', $module['capabilities']) }}</td></tr>@endforeach
        </tbody></table></div>
    </section>
    <p>Use each capability's dedicated presentation adapter for operational records. This package intentionally does not query another module's tables.</p>
</x-filament-panels::page>
