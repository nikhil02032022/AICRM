{{-- EC-002 — Edit programme interest modal/inline form --}}
<form method="POST" action="{{ route('crm.leads.programme-interests.update', [$lead->uuid, $programme->uuid]) }}" class="space-y-6">
    @csrf
    @method('PUT')
    <div>
        <label for="status" class="block text-xs font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            @foreach($statusOptions as $opt)
                <option value="{{ $opt['value'] }}" @selected($pivot?->status === $opt['value'])>{{ $opt['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="preferred_intake" class="block text-xs font-medium text-gray-700">Preferred Intake</label>
        <input type="text" name="preferred_intake" id="preferred_intake" maxlength="100" value="{{ old('preferred_intake', $pivot?->preferred_intake) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
    </div>
    <div>
        <label for="notes" class="block text-xs font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" maxlength="2000" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('notes', $pivot?->notes) }}</textarea>
    </div>
    <div class="flex justify-end gap-2">
        <a href="{{ route('crm.leads.show', $lead->uuid) }}" class="btn-secondary-sm">Cancel</a>
        <button type="submit" class="btn-primary-sm">Save</button>
    </div>
</form>
