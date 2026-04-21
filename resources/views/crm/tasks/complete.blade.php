<x-layouts.crm title="Complete Task">
    <div class="space-y-6 max-w-xl">

        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li>
                    <a href="{{ route('crm.tasks.index') }}"
                        class="flex items-center gap-1 hover:text-gray-900 transition-colors duration-150">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Tasks
                    </a>
                </li>
                <li aria-hidden="true">
                    <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </li>
                <li class="font-medium text-gray-900" aria-current="page">Mark Complete</li>
            </ol>
        </nav>

        {{-- Page header --}}
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100">
                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mark Task Complete</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Log the outcome and close this task</p>
                </div>
            </div>
            <a href="{{ route('crm.tasks.index') }}" class="btn-secondary" aria-label="Back to tasks list">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>

        {{-- Task summary --}}
        <div class="card p-5 space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-900">{{ $task->title }}</p>
                @if ($task->type)
                <span class="badge badge-primary">{{ $task->type->label() }}</span>
                @endif
            </div>
            @if ($task->lead)
            <p class="text-sm text-gray-600">
                Lead: <span class="font-medium text-gray-800">{{ $task->lead->first_name }} {{ $task->lead->last_name }}</span>
            </p>
            @endif
            @if ($task->due_at)
            <p class="text-sm text-gray-500">
                Due: {{ $task->due_at->format('d M Y, g:i A') }}
                @if ($task->isOverdue())
                <span class="badge badge-red ml-1">Overdue</span>
                @endif
            </p>
            @endif
        </div>

        {{-- Completion form --}}
        <form
            action="{{ route('crm.tasks.complete.store', $task->uuid) }}"
            method="POST"
            x-data="{ submitting: false }"
            @submit="submitting = true"
            novalidate
        >
            @csrf

            <div class="card space-y-6">

                {{-- Disposition --}}
                <div>
                    <label for="disposition" class="label">
                        Outcome / Disposition <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select id="disposition" name="disposition" required
                        @class([
                            'input-field',
                            'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('disposition'),
                        ])
                        aria-required="true">
                        <option value="">— Select an outcome —</option>
                        @foreach ($dispositions as $disposition)
                        <option value="{{ $disposition->value }}"
                            {{ old('disposition') === $disposition->value ? 'selected' : '' }}>
                            {{ $disposition->label() }}
                        </option>
                        @endforeach
                    </select>
                    @error('disposition')
                    <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="label">
                        Notes <span class="font-normal text-gray-400">(optional)</span>
                    </label>
                    <textarea id="notes" name="notes" rows="4" maxlength="2000"
                        @class([
                            'input-field resize-none',
                            'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('notes'),
                        ])
                        placeholder="Add any relevant notes about this interaction...">{{ old('notes') }}</textarea>
                    @error('notes')
                    <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                    <a href="{{ route('crm.tasks.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit"
                        class="btn-primary"
                        :disabled="submitting"
                        x-bind:class="submitting ? 'opacity-50 cursor-not-allowed' : ''">
                        <span x-show="!submitting" class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Mark Complete
                        </span>
                        <span x-show="submitting" class="flex items-center gap-2" style="display:none">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                            </svg>
                            Saving…
                        </span>
                    </button>
                </div>

            </div>
        </form>

    </div>
</x-layouts.crm>
