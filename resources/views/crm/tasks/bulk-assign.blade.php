<x-layouts.crm title="Bulk Assign Tasks">
    @php
        $preselected = collect(explode(',', request('uuids', '')))->filter()->values();
    @endphp

    <div class="space-y-6 max-w-3xl">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bulk Assign Tasks</h1>
                @if ($preselected->isNotEmpty())
                <p class="mt-1 text-sm text-gray-500">{{ $preselected->count() }} task(s) selected from the task list</p>
                @endif
            </div>
            <a href="{{ route('crm.tasks.index') }}" class="btn-secondary">← Back to Tasks</a>
        </div>

        @if (session('success'))
        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 border border-green-200">{{ session('success') }}</div>
        @endif

        @if ($preselected->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            No tasks selected. Go to the
            <a href="{{ route('crm.tasks.index') }}" class="font-semibold underline">task list</a>,
            tick the checkboxes, then click <strong>Bulk Assign</strong>.
        </div>
        @else
        <form action="{{ route('crm.tasks.bulk-reassign') }}" method="POST"
              class="space-y-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @csrf

            @foreach ($preselected as $uuid)
            <input type="hidden" name="task_uuids[]" value="{{ $uuid }}">
            @endforeach

            {{-- Assignee --}}
            <div>
                <label for="assigned_to" class="label">Assign To <span class="text-red-500">*</span></label>
                <select id="assigned_to" name="assigned_to"
                    @class(['input-field', 'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('assigned_to')])>
                    <option value="">— Select a counsellor —</option>
                    @foreach ($users as $user)
                    <option value="{{ $user->id }}"
                        {{ old('assigned_to', auth()->id()) == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                        @if ($user->id === auth()->id()) (you) @endif
                    </option>
                    @endforeach
                </select>
                @error('assigned_to')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
            </div>

            @error('task_uuids')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            @error('task_uuids.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <a href="{{ route('crm.tasks.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Reassign {{ $preselected->count() }} Task(s)</button>
            </div>
        </form>
        @endif

    </div>
</x-layouts.crm>
