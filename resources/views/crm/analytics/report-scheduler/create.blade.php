<x-layouts.crm title="New Scheduled Report">
    <div
        class="max-w-2xl space-y-6"
        x-data="{
            frequency: '{{ old('frequency', 'weekly') }}',
            emails: @json(old('recipient_emails', [])),
            emailInput: '',
            addEmail() {
                const e = this.emailInput.trim().toLowerCase();
                if (e && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e) && !this.emails.includes(e)) {
                    this.emails.push(e);
                }
                this.emailInput = '';
            },
            removeEmail(i) { this.emails.splice(i, 1); },
        }"
    >

        {{-- Page header --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('crm.reports.scheduler.index') }}"
               aria-label="Back to Scheduled Reports"
               class="flex items-center justify-center h-9 w-9 rounded-lg border border-gray-200 bg-white text-gray-400 shadow-sm hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">New Scheduled Report</h1>
                <p class="text-sm text-gray-500">Set up automatic report delivery on a schedule.</p>
            </div>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('crm.reports.scheduler.store') }}" class="space-y-5">
            @csrf

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Schedule Details</h2>
                </div>
                <div class="p-5 space-y-5">

                    {{-- Report selection --}}
                    <div>
                        <label for="custom_report_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Report <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="custom_report_id" name="custom_report_id" required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Select a report —</option>
                            @foreach($reports as $report)
                                <option value="{{ $report->id }}" {{ old('custom_report_id') == $report->id ? 'selected' : '' }}>
                                    {{ $report->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('custom_report_id')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Frequency --}}
                    <div>
                        <label for="frequency" class="block text-sm font-medium text-gray-700 mb-1">
                            Frequency <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="frequency" name="frequency" x-model="frequency" required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Enums\CRM\ReportFrequency::cases() as $freq)
                                <option value="{{ $freq->value }}" {{ old('frequency') === $freq->value ? 'selected' : '' }}>
                                    {{ ucfirst($freq->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('frequency')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Day of week (weekly only) --}}
                    <template x-if="frequency === 'weekly'">
                        <div>
                            <label for="day_of_week" class="block text-sm font-medium text-gray-700 mb-1">Day of Week</label>
                            <select id="day_of_week" name="day_of_week"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $i => $day)
                                    <option value="{{ $i + 1 }}" {{ old('day_of_week') == ($i + 1) ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    {{-- Day of month (monthly only) --}}
                    <template x-if="frequency === 'monthly'">
                        <div>
                            <label for="day_of_month" class="block text-sm font-medium text-gray-700 mb-1">Day of Month (1–28)</label>
                            <input type="number" id="day_of_month" name="day_of_month" min="1" max="28"
                                value="{{ old('day_of_month', 1) }}"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </template>

                    {{-- Run time --}}
                    <div>
                        <label for="run_time" class="block text-sm font-medium text-gray-700 mb-1">
                            Run Time (HH:MM) <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="time" id="run_time" name="run_time" value="{{ old('run_time', '08:00') }}" required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('run_time')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Delivery format --}}
                    <div>
                        <label for="format" class="block text-sm font-medium text-gray-700 mb-1">
                            Delivery Format <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="format" name="format" required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Enums\CRM\ReportFormat::cases() as $fmt)
                                <option value="{{ $fmt->value }}" {{ old('format') === $fmt->value ? 'selected' : '' }}>
                                    {{ strtoupper($fmt->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('format')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Recipient emails --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Recipient Emails <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 shadow-sm min-h-[46px] focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition-colors duration-150">
                            <template x-for="(email, i) in emails" :key="i">
                                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">
                                    <span x-text="email"></span>
                                    <button type="button" @click="removeEmail(i)"
                                        :aria-label="'Remove ' + email"
                                        class="text-indigo-400 hover:text-indigo-700 cursor-pointer focus:outline-none">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <input type="hidden" name="recipient_emails[]" :value="email">
                                </span>
                            </template>
                            <input
                                type="email"
                                x-model="emailInput"
                                @keydown.enter.prevent="addEmail()"
                                @keydown.tab.prevent="addEmail()"
                                placeholder="Add email and press Enter…"
                                class="flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder-gray-400 focus:ring-0 outline-none min-w-[200px]"
                            >
                        </div>
                        @error('recipient_emails')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-1">
                <a href="{{ route('crm.reports.scheduler.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Create Schedule
                </button>
            </div>

        </form>
    </div>
</x-layouts.crm>
