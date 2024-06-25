<div>
    <form wire:submit.prevent="generateReport(selectedPersonId)">
        <div class="form-group">
            <label for="personSelect">Select a Person:</label>
            <select id="personSelect" class="form-control" wire:model="selectedPersonId">
                <option value="">--Choose--</option>
                @foreach(\App\Models\Person::all() as $person)
                    <option value="{{ $person->id }}">{{ $person->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </form>

    <div wire:loading>
        Generating report...
    </div>

    @if(!empty($reportData))
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Birth Date</th>
                    <th>Death Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                    <tr>
                        <td>{{ $data['name'] }}</td>
                        <td>{{ $data['birth'] ?? 'N/A' }}</td>
                        <td>{{ $data['death'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
