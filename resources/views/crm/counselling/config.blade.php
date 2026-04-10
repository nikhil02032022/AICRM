{{-- BRD: CRM-EC-006 — Assignment configuration (mode, cap, escalation) --}}
<x-layouts.crm title="Assignment Configuration">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Counsellor Assignment Configuration</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Configure how leads are distributed to counsellors for your institution.
                </p>
            </div>
            <a href="{{ route('crm.leads.index') }}" class="btn-secondary-sm">Back to Leads</a>
        </div>

        @if(session('success'))
            <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3" role="alert">
                <svg class="h-5 w-5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        <form method="POST"
              action="{{ route('crm.settings.assignment-config.update') }}"
              class="card divide-y divide-gray-100">
            @csrf
            @method('POST')

            {{-- Assignment mode --}}
            <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <p class="text-sm font-semibold text-gray-800">Assignment Mode</p>
                    <p class="mt-0.5 text-xs text-gray-500">How incoming leads are distributed.</p>
                </div>
                <div class="sm:col-span-2">
                    @foreach(\App\Enums\CRM\AssignmentMode::cases() as $mode)
                        <label class="mb-2 flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50
                               {{ $config->assignment_mode === $mode ? 'border-primary-300 bg-primary-50/50' : '' }}">
                            <input type="radio"
                                   name="assignment_mode"
                                   value="{{ $mode->value }}"
                                   class="mt-0.5 accent-primary-600"
                                   {{ $config->assignment_mode === $mode ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $mode->label() }}</p>
                                <p class="text-xs text-gray-500">{{ $mode->description() }}</p>
                            </div>
                        </label>
                    @endforeach
                    @error('assignment_mode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Lead cap --}}
            <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <p class="text-sm font-semibold text-gray-800">Leads Per Counsellor (Max)</p>
                    <p class="mt-0.5 text-xs text-gray-500">Auto-assignment stops when this cap is reached.</p>
                </div>
                <div class="sm:col-span-2">
                    <input type="number"
                           name="max_leads_per_counsellor"
                           min="1" max="500"
                           value="{{ old('max_leads_per_counsellor', $config->max_leads_per_counsellor) }}"
                           class="input-field w-32"
                           required>
                    @error('max_leads_per_counsellor')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Escalation --}}
            <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <p class="text-sm font-semibold text-gray-800">Escalation Threshold (hours)</p>
                    <p class="mt-0.5 text-xs text-gray-500">Unactioned leads older than this will be escalated.</p>
                </div>
                <div class="sm:col-span-2 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Hours before escalation</label>
                        <input type="number"
                               name="escalation_hours"
                               min="1" max="720"
                               value="{{ old('escalation_hours', $config->escalation_hours) }}"
                               class="input-field w-32"
                               required>
                        @error('escalation_hours')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Escalate to (user)</label>
                        <input type="number"
                               name="escalation_to_user_id"
                               value="{{ old('escalation_to_user_id', $config->escalation_to_user_id) }}"
                               class="input-field w-48"
                               placeholder="User ID (optional)">
                        @error('escalation_to_user_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3 p-5">
                <a href="{{ route('crm.leads.index') }}" class="btn-secondary-sm">Cancel</a>
                <button type="submit" class="btn-primary-sm">Save Configuration</button>
            </div>
        </form>

    </div>
</x-layouts.crm>
