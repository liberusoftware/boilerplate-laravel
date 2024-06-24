<div>
    <div class="flex justify-center my-4">
        <input type="text" class="form-input px-4 py-2 border rounded w-full max-w-xl" placeholder="Search by name..." wire:model="query">
    </div>
    <div wire:loading class="text-center text-gray-500">Loading...</div>
    <ul class="list-none space-y-2 mt-3">
        @forelse($results as $person)
            <li class="p-4 shadow rounded hover:bg-gray-100">
                <div class="font-semibold">{{ $person->givn }} {{ $person->surn }}</div>
            </li>
        @empty
            <li class="text-center text-gray-500">No results found.</li>
        @endforelse
    </ul>
</div>
