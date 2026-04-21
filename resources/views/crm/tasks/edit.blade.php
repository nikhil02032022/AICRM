<x-layouts.crm title="Edit Task">
    <div class="space-y-6">

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
                <li class="max-w-xs truncate font-medium text-gray-900" aria-current="page" title="{{ $task->title }}">
                    {{ Str::limit($task->title, 40) }}
                </li>
            </ol>
        </nav>

        {{-- Page header --}}
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100">
                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Task</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Last updated {{ $task->updated_at->diffForHumans() }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($task->status)
                <span class="badge {{ $task->status->tailwindBadgeClass() }}">{{ $task->status->label() }}</span>
                @endif
                <a href="{{ route('crm.tasks.index') }}"
                    class="btn-secondary"
                    aria-label="Back to tasks list">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Two-panel layout --}}
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">

            {{-- ── LEFT: Main Form (2/3) ────────────────────────────── --}}
            <div class="lg:col-span-2">
                <form
                    action="{{ route('crm.tasks.update', $task->uuid) }}"
                    method="POST"
                    x-data="{ submitting: false }"
                    @submit="submitting = true"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    <div class="card space-y-8 p-0 overflow-hidden">

                        {{-- Section 1: Lead & Title --}}
                        <div class="px-6 pt-6 space-y-5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">1</span>
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Task Details</h2>
                            </div>

                            {{-- Lead --}}
                            <div>
                                <label for="lead_id" class="label">Lead</label>
                                <select id="lead_id" name="lead_id"
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('lead_id'),
                                    ])>
                                    <option value="">— No lead linked —</option>
                                    @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}"
                                        {{ old('lead_id', $task->lead_id) == $lead->id ? 'selected' : '' }}>
                                        {{ $lead->first_name }} {{ $lead->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('lead_id')
                                <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Title --}}
                            <div>
                                <label for="title" class="label">Title</label>
                                <input type="text" id="title" name="title"
                                    value="{{ old('title', $task->title) }}"
                                    maxlength="180"
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('title'),
                                    ])>
                                @error('title')
                                <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="description" class="label">Notes / Description</label>
                                <textarea id="description" name="description"
                                    rows="3"
                                    maxlength="2000"
                                    @class([
                                        'input-field resize-none',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('description'),
                                    ])>{{ old('description', $task->description) }}</textarea>
                                @error('description')
                                <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-gray-100"></div>

                        {{-- Section 2: Task Type --}}
                        <div class="px-6 space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">2</span>
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Task Type</h2>
                            </div>

                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5" role="radiogroup" aria-label="Task type">
                                @foreach ($types as $type)
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="type" value="{{ $type->value }}"
                                        class="sr-only peer"
                                        {{ $task->type?->value === $type->value ? 'checked' : '' }}>
                                    <div class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-gray-200 p-4 text-center
                                        transition-all duration-150
                                        peer-checked:border-primary-500 peer-checked:bg-primary-50
                                        peer-focus:ring-2 peer-focus:ring-primary-500 peer-focus:ring-offset-2
                                        group-hover:border-gray-300 group-hover:bg-gray-50">

                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 transition-colors duration-150">
                                            @switch($type->value)
                                                @case('call')
                                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                                                    </svg>
                                                @break
                                                @case('email')
                                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                                                    </svg>
                                                @break
                                                @case('whatsapp')
                                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                                                    </svg>
                                                @break
                                                @case('meeting')
                                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                                    </svg>
                                                @break
                                                @case('document_review')
                                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                                    </svg>
                                                @break
                                            @endswitch
                                        </div>

                                        <span class="text-xs font-medium text-gray-700 leading-tight">
                                            {{ $type->label() }}
                                        </span>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('type')
                            <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-gray-100"></div>

                        {{-- Section 3: Priority, Status & Due Date --}}
                        <div class="px-6 space-y-5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">3</span>
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Priority, Status & Schedule</h2>
                            </div>

                            {{-- Priority --}}
                            <div>
                                <label for="priority" class="label">
                                    Priority <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <select id="priority" name="priority" required
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('priority'),
                                    ])
                                    aria-required="true">
                                    @foreach ($priorities as $priority)
                                    <option value="{{ $priority->value }}"
                                        {{ old('priority', $task->priority?->value) === $priority->value ? 'selected' : '' }}>
                                        {{ $priority->label() }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('priority')
                                <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div>
                                <label for="status" class="label">Status</label>
                                <select id="status" name="status"
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('status'),
                                    ])>
                                    @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}"
                                        {{ $task->status?->value === $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('status')
                                <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Due Date --}}
                            <div>
                                <label for="due_at" class="label">Due Date & Time</label>
                                <input type="datetime-local" id="due_at" name="due_at"
                                    value="{{ old('due_at', $task->due_at?->format('Y-m-d\TH:i')) }}"
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('due_at'),
                                    ])>
                                @error('due_at')
                                <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                                @if ($task->isOverdue())
                                <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    This task is currently overdue. Update the due date or mark as complete.
                                </p>
                                @endif
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-gray-100"></div>

                        {{-- Form footer --}}
                        <div class="flex items-center justify-between px-6 pb-6">
                            <a href="{{ route('crm.tasks.index') }}" class="btn-secondary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancel
                            </a>

                            <button type="submit"
                                class="btn-primary"
                                :disabled="submitting"
                                x-bind:class="submitting ? 'opacity-50 cursor-not-allowed' : ''">
                                <span x-show="!submitting" class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Save Changes
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

            {{-- ── RIGHT: Context Panel (1/3) ──────────────────────── --}}
            <div class="mt-6 space-y-4 lg:mt-0">

                {{-- Task meta card --}}
                <div class="card p-5 space-y-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700">Task Info</h3>
                    </div>
                    <dl class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500">Created</dt>
                            <dd class="font-medium text-gray-700">{{ $task->created_at->format('d M Y, H:i') }}</dd>
                        </div>
                        @if ($task->lead)
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500">Lead</dt>
                            <dd class="font-medium text-gray-700">{{ $task->lead->first_name }} {{ $task->lead->last_name }}</dd>
                        </div>
                        @endif
                        @if ($task->assignee)
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500">Assigned to</dt>
                            <dd class="font-medium text-gray-700">{{ $task->assignee->name }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                {{-- Assignment card --}}
                <div class="card p-5 space-y-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700">Re-assign</h3>
                    </div>
                    <div>
                        <label for="assigned_to" class="label">Assign To</label>
                        <select id="assigned_to" name="assigned_to"
                            @class([
                                'input-field',
                                'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('assigned_to'),
                            ])>
                            <option value="">— Keep current assignee —</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('assigned_to', $task->assigned_to) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                                @if ($user->id === auth()->id()) (you) @endif
                            </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                        <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Danger zone --}}
                @can('delete', $task)
                <div class="rounded-xl border border-red-200 bg-red-50 p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-red-700">Danger Zone</h3>
                    </div>
                    <p class="text-xs text-red-600">Cancelling this task cannot be undone. The task will be marked as cancelled and removed from active queues.</p>
                    <form action="{{ route('crm.tasks.destroy', $task->uuid) }}"
                        method="POST"
                        x-data
                        @submit.prevent="
                            $el.querySelector('[type=submit]').disabled = true;
                            if (confirm('Are you sure you want to cancel this task?')) { $el.submit(); }
                            else { $el.querySelector('[type=submit]').disabled = false; }
                        ">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="btn-danger w-full justify-center text-xs"
                            aria-label="Cancel this task permanently">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Cancel Task
                        </button>
                    </form>
                </div>
                @endcan

            </div>
        </div>

    </div>
</x-layouts.crm>
