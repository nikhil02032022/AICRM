<x-layouts.crm title="Bulk Assign Tasks">
    <div class="space-y-6 max-w-3xl">

        <h1 class="text-2xl font-bold text-gray-900">Bulk Assign Tasks</h1>

        <form action="{{ route('crm.tasks.bulk-reassign') }}" method="POST"
              class="space-y-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6"
              x-data="{ selected: [], selectAll: false }"
              @change.capture="if ($event.target.name === 'task_uuids[]') { selected = [...document.querySelectorAll('input[name=\'task_uuids[]\']:checked')].map(el => el.value); }">
            @csrf

            @if (session('success'))
            <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 border border-green-200">{{ session('success') }}</div>
            @endif

            {{-- Assignee --}}
            <div>
                <label for="assigned_to" class="block text-sm font-medium text-gray-700">
                    Assign To (User ID) <span class="text-red-500">*</span>
                </label>
                <input type="number" id="assigned_to" name="assigned_to" value="{{ old('assigned_to') }}"
                    class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('assigned_to') border-red-400 @enderror"
                    required>
                @error('assigned_to')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Task UUIDs (hidden inputs — populated by the task list checkboxes) --}}
            <div>
                <p class="text-sm text-gray-600">
                    Select tasks from the <a href="{{ route('crm.tasks.index') }}" class="text-indigo-600 hover:underline">task list</a> to bulk-assign them here.
                </p>
                @error('task_uuids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('task_uuids.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <a href="{{ route('crm.tasks.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Reassign Selected</button>
            </div>
        </form>

    </div>
</x-layouts.crm>
