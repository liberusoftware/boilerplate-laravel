<x-filament-panels::page>
    <div class="space-y-4">
        @forelse ($conversations as $conversation)
            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <div class="flex items-center justify-between gap-4">
                    <span class="font-medium">Participant {{ $conversation['participant_id'] }}</span>
                    @if ($conversation['unread_count'] > 0)
                        <x-filament::badge>{{ $conversation['unread_count'] }} unread</x-filament::badge>
                    @endif
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $conversation['last_message']['body'] }}</p>
            </div>
        @empty
            <x-filament::section>No conversations yet.</x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
