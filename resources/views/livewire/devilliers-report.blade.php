<div>
    <form wire:submit.prevent="generateReport">
        <div class="flex items-end">
            <div class="flex-1 pr-2">{{ $this->form }}</div>
            {{-- <div>{{ $this->generateAction }}</div> --}}
        </div>
    </form>

    <div class="flex justify-center text-center mt-3" wire:loading>
        Generating report...
    </div>

    @if(!empty($reportData))
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>d'Aboville Number</th>
                    <th>Name</th>
                    <th>Birth Date</th>
                    <th>Death Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                    <tr>
                        <td>{{ $data['number'] }}</td>
                        <td>{{ $data['name'] }}</td>
                        <td>{{ $data['birth'] }}</td>
                        <td>{{ $data['death'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

