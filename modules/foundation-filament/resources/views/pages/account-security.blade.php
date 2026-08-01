<x-filament-panels::page>
    <section aria-labelledby="account-preferences-heading" class="space-y-3">
        <h2 id="account-preferences-heading" class="text-lg font-semibold">Preferences</h2>
        <dl class="grid gap-2 sm:grid-cols-2">
            <div><dt class="font-medium">Locale</dt><dd>{{ auth()->user()->locale ?? app()->getLocale() }}</dd></div>
            <div><dt class="font-medium">Timezone</dt><dd>{{ auth()->user()->timezone ?? 'UTC' }}</dd></div>
            <div><dt class="font-medium">Appearance</dt><dd>{{ auth()->user()->theme_preference ?? 'Site default' }}</dd></div>
            <div><dt class="font-medium">Currency</dt><dd>{{ config('currency.display', config('currency.base')) }}</dd></div>
        </dl>
    </section>

    <section aria-labelledby="sessions-heading" class="space-y-3">
        <h2 id="sessions-heading" class="text-lg font-semibold">Active sessions</h2>
        <ul class="divide-y" role="list">
            @forelse ($sessions as $session)
                <li class="flex items-center justify-between gap-4 py-3">
                    <span><strong>{{ $session->is_current ? 'Current session' : 'Session' }}</strong><br>{{ $session->ip_address ?? 'Unknown network' }} · {{ date('Y-m-d H:i', $session->last_activity) }} UTC</span>
                    @unless ($session->is_current)<button type="button" wire:click="revoke('{{ $session->id }}')" class="fi-btn">Revoke</button>@endunless
                </li>
            @empty
                <li class="py-3">No persisted sessions were found.</li>
            @endforelse
        </ul>
    </section>

    <p>Use the account security actions to update your password, configure two-factor authentication, review API tokens, or delete your account. Sensitive changes require recent authentication.</p>
</x-filament-panels::page>
