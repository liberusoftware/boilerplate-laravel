<x-filament-panels::page>
    <section aria-labelledby="module-heading" class="space-y-3">
        <h2 id="module-heading" class="text-lg font-semibold">Installed modules</h2>
        <div class="overflow-x-auto"><table class="w-full"><thead><tr><th scope="col">Module</th><th scope="col">Version</th><th scope="col">Capabilities</th></tr></thead><tbody>
        @foreach ($modules as $module)<tr><th scope="row">{{ $module['display_name'] }}</th><td>{{ $module['version'] }}</td><td>{{ implode(', ', $module['capabilities']) }}</td></tr>@endforeach
        </tbody></table></div>
    </section>
    <section aria-labelledby="operations-heading" class="space-y-3">
        <h2 id="operations-heading" class="text-lg font-semibold">Operational records</h2>
        <dl class="grid gap-3 sm:grid-cols-3">@foreach ($diagnostics as $name => $count)<div><dt>{{ str($name)->replace('_', ' ')->title() }}</dt><dd>{{ $count ?? 'Not installed' }}</dd></div>@endforeach</dl>
        <p>Use the dedicated Horizon, Telescope, Pulse, settings, roles, teams, and resource pages for authorized operations. Replay, lifecycle, and schema changes remain explicit audited deployment actions.</p>
    </section>
</x-filament-panels::page>
