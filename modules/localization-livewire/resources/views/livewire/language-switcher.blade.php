<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = ! open" class="inline-flex items-center gap-2" aria-label="Change language">{{ strtoupper($currentLocale) }}</button>
    <div x-show="open" @click.outside="open = false" class="absolute right-0 z-50 mt-2 min-w-40 rounded-lg bg-white py-1 shadow dark:bg-gray-800">
        @foreach($availableLocales as $locale => $name)
            <button wire:click="switchLanguage('{{ $locale }}')" class="block w-full px-4 py-2 text-left text-sm">{{ $name }}</button>
        @endforeach
    </div>
</div>
