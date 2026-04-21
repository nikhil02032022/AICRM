<x-layouts.crm title="Create Task">
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
                <li class="font-medium text-gray-900" aria-current="page">Create Task</li>
            </ol>
        </nav>

        {{-- Page header --}}
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100">
                    <svg class="h-5 w-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Task</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Schedule a follow-up activity against a lead</p>
                </div>
            </div>
            <a href="{{ route('crm.tasks.index') }}"
                class="btn-secondary"
                aria-label="Back to tasks list">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>

        {{-- Two-panel layout --}}
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">

            {{-- ── LEFT: Main Form (2/3) ────────────────────────────── --}}
            <div class="lg:col-span-2">
                <form
                    action="{{ route('crm.tasks.store') }}"
                    method="POST"
                    x-data="{ submitting: false }"
                    @submit="submitting = true"
                    novalidate
                >
                    @csrf

                    <div class="card space-y-8 p-0 overflow-hidden">

                        {{-- Section 1: Lead & Title --}}
                        <div class="px-6 pt-6 space-y-5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">1</span>
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Task Details</h2>
                            </div>

                            {{-- Lead --}}
                            <div>
                                <label for="lead_id" class="label">
                                    Lead <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <select id="lead_id" name="lead_id" required
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('lead_id'),
                                    ])
                                    aria-describedby="{{ $errors->has('lead_id') ? 'lead_id-error' : null }}"
                                    aria-required="true">
                                    <option value="">— Select a lead —</option>
                                    @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" {{ old('lead_id') == $lead->id ? 'selected' : '' }}>
                                        {{ $lead->first_name }} {{ $lead->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('lead_id')
                                <p id="lead_id-error" class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Title --}}
                            <div>
                                <label for="title" class="label">
                                    Title <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="title" name="title"
                                    value="{{ old('title') }}"
                                    maxlength="180"
                                    placeholder="e.g. Follow up call re: MBA programme interest"
                                    required
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('title'),
                                    ])
                                    aria-describedby="{{ $errors->has('title') ? 'title-error' : null }}"
                                    aria-required="true">
                                @error('title')
                                <p id="title-error" class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="description" class="label">Notes / Description</label>
                                <textarea id="description" name="description"
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Optional context, talking points, or preparation notes…"
                                    @class([
                                        'input-field resize-none',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('description'),
                                    ])>{{ old('description') }}</textarea>
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
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">2</span>
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Task Type <span class="text-red-500" aria-hidden="true">*</span></h2>
                            </div>

                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5" role="radiogroup" aria-label="Task type">
                                @foreach ($types as $type)
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="type" value="{{ $type->value }}"
                                        class="sr-only peer"
                                        {{ old('type') === $type->value ? 'checked' : '' }}
                                        required>
                                    <div class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-gray-200 p-4 text-center
                                        transition-all duration-150
                                        peer-checked:border-primary-500 peer-checked:bg-primary-50
                                        peer-focus:ring-2 peer-focus:ring-primary-500 peer-focus:ring-offset-2
                                        group-hover:border-gray-300 group-hover:bg-gray-50">

                                        {{-- SVG icon per type --}}
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100
                                            peer-checked:bg-primary-100 transition-colors duration-150
                                            group-[.peer:checked~div]:bg-primary-100">
                                            @switch($type->value)
                                                @case('call')
                                                    <svg class="h-4 w-4 text-gray-500 peer-checked:text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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

                                        <span class="text-xs font-medium text-gray-700 peer-checked:text-primary-700 leading-tight">
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

                        {{-- Section 3: Priority & Due Date --}}
                        <div class="px-6 space-y-5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">3</span>
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Priority & Schedule</h2>
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
                                        {{ old('priority', 'normal') === $priority->value ? 'selected' : '' }}>
                                        {{ $priority->label() }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('priority')
                                <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Due Date --}}
                            <div>
                                <label for="due_at" class="label">
                                    Due Date & Time <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input type="datetime-local" id="due_at" name="due_at"
                                    value="{{ old('due_at') }}"
                                    min="{{ now()->format('Y-m-d\TH:i') }}"
                                    required
                                    @class([
                                        'input-field',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('due_at'),
                                    ])
                                    aria-describedby="{{ $errors->has('due_at') ? 'due_at-error' : null }}"
                                    aria-required="true">
                                @error('due_at')
                                <p id="due_at-error" class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Create Task
                                </span>
                                <span x-show="submitting" class="flex items-center gap-2" style="display:none">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                                    </svg>
                                    Creating…
                                </span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- ── RIGHT: Context Panel (1/3) ──────────────────────── --}}
            <div class="mt-6 space-y-4 lg:mt-0">

                {{-- Assignment card --}}
                <div class="card p-5 space-y-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700">Assignment</h3>
                    </div>

                    <div>
                        <label for="assigned_to" class="label">Assign To</label>
                        <select id="assigned_to" name="assigned_to"
                            @class([
                                'input-field',
                                'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('assigned_to'),
                            ])>
                            <option value="">— Assign to yourself —</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('assigned_to', auth()->id()) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                                @if ($user->id === auth()->id()) (you) @endif
                            </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                        <p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-gray-400">Defaults to you if left blank.</p>
                    </div>
                </div>

                {{-- Task type guide --}}
                <div class="card p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700">Task Type Guide</h3>
                    </div>
                    <ul class="space-y-2.5 text-xs text-gray-500">
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                            </svg>
                            <span><strong class="text-gray-700">Call</strong> — Outbound or follow-up phone call</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                            </svg>
                            <span><strong class="text-gray-700">Email</strong> — Send programme brochure or update</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                            </svg>
                            <span><strong class="text-gray-700">WhatsApp</strong> — Quick touchpoint via messaging</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                            </svg>
                            <span><strong class="text-gray-700">Meeting</strong> — Campus visit or video consultation</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            <span><strong class="text-gray-700">Doc Review</strong> — Review application documents</span>
                        </li>
                    </ul>
                </div>

                {{-- Priority guide --}}
                <div class="card p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700">Priority Guide</h3>
                    </div>
                    <ul class="space-y-2 text-xs text-gray-500">
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>
                            <span><strong class="text-gray-700">Urgent</strong> — Action needed within hours</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-orange-400"></span>
                            <span><strong class="text-gray-700">High</strong> — Due today or tomorrow</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-blue-400"></span>
                            <span><strong class="text-gray-700">Normal</strong> — Routine follow-up</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-gray-400"></span>
                            <span><strong class="text-gray-700">Low</strong> — No immediate deadline</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

    </div>
</x-layouts.crm>
