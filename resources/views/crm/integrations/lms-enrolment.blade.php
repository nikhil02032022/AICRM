{{-- BRD: EI-010 — LMS Auto-Enrolment: trigger CamPLUS/Moodle enrolment for enrolled students --}}
<x-layouts.crm title="LMS Enrolment">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">LMS Auto-Enrolment</h1>
                <p class="mt-1 text-sm text-gray-500">Trigger automatic course enrolment in CamPLUS or Moodle for confirmed students.</p>
            </div>
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'trigger-lms-enrolment')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Trigger Enrolment
            </button>
        </div>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Provider filter tabs --}}
    <div class="mb-4 flex gap-2" x-data="{ active: '{{ request('provider', 'all') }}' }">
        @foreach(['all' => 'All', 'camplus' => 'CamPLUS', 'moodle' => 'Moodle'] as $val => $label)
            <a href="{{ route('crm.integrations.lms-enrolment.index', array_merge(request()->query(), ['provider' => $val])) }}"
               class="rounded-full border px-3 py-1 text-xs font-medium transition
                   {{ request('provider', 'all') === $val ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Enrolment Logs Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lead</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">LMS Provider</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Course ID</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">LMS User ID</th>
                    <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">Attempts</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Triggered</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $log->lead?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded border border-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700 bg-gray-50">
                                {{ Str::upper($log->lms_provider ?? '—') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $log->lms_course_id ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $log->lms_user_id ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold
                                {{ ($log->attempt_count ?? 0) >= 3 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $log->attempt_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $log->status->color(); @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $log->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                            No LMS enrolment records found. Click <span class="font-semibold">Trigger Enrolment</span> to enrol a student.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Trigger Modal --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail === 'trigger-lms-enrolment') open = true"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        style="display:none"
        x-cloak
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Trigger LMS Enrolment</h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('crm.integrations.lms-enrolment.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ERP Student ID</label>
                    <input type="text" name="erp_student_id" required placeholder="ERP Student Master ID"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        value="{{ old('erp_student_id') }}">
                    @error('erp_student_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="lms_lead_uuid">Lead</label>
                    <select id="lms_lead_uuid" name="lead_uuid" required
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">— Select lead —</option>
                        @foreach($leads as $lead)
                            <option value="{{ $lead->uuid }}" {{ old('lead_uuid') === $lead->uuid ? 'selected' : '' }}>
                                {{ trim($lead->first_name . ' ' . $lead->last_name) }} ({{ $lead->mobile }})
                            </option>
                        @endforeach
                    </select>
                    @error('lead_uuid')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">LMS Provider</label>
                    <select name="lms_provider" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">— Select —</option>
                        <option value="camplus" {{ old('lms_provider') === 'camplus' ? 'selected' : '' }}>CamPLUS</option>
                        <option value="moodle" {{ old('lms_provider') === 'moodle' ? 'selected' : '' }}>Moodle</option>
                    </select>
                    @error('lms_provider')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">LMS Course ID</label>
                    <input type="text" name="lms_course_id" required placeholder="LMS-specific course identifier"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        value="{{ old('lms_course_id') }}">
                    @error('lms_course_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Trigger Enrolment</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.crm>
