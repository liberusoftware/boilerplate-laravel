<x-filament-panels::page.simple>
{{Str::markdown(file_get_contents(\Filament\Jetstream\Jetstream::plugin()?->policyMarkdown))}}
</x-filament-panels::page.simple>
