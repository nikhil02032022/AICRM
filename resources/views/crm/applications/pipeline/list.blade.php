<x-layouts.crm>
    <x-slot:header>Application List</x-slot:header>

    <div
        class="space-y-6"
        x-data="{
            selected: [],
            toggleAll(event) {
                const checked = event.target.checked;
                const boxes = Array.from(document.querySelectorAll('.js-app-select'));
                boxes.forEach((box) => {
                    box.checked = checked;
                });
                this.selected = checked ? boxes.map((box) => box.value) : [];
            },
            sync(uuid, checked) {
                if (checked && !this.selected.includes(uuid)) {
                    this.selected.push(uuid);
                }
                if (!checked) {
                    this.selected = this.selected.filter((item) => item !== uuid);
                }
            }
        }"
    >
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Application Filters</h2>
            <p class="mt-2 text-sm text-gray-600">Filter by programme, batch, counsellor, source, status, date range, and score.</p>

            <form method="GET" action="{{ route('crm.applications.list') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="q" class="block text-sm font-medium text-gray-700">Search</label>
                    <input id="q" name="q" type="text" value="{{ $filters['search'] ?? '' }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Name or email" />
                </div>

                <div>
                    <label for="programme_id" class="block text-sm font-medium text-gray-700">Programme</label>
                    <select id="programme_id" name="programme_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <option value="">All Programmes</option>
                        @foreach ($programmes as $programme)
                            <option value="{{ $programme->id }}" @selected((string) $programme->id === (string) ($filters['programme_id'] ?? ''))>
                                {{ $programme->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="batch" class="block text-sm font-medium text-gray-700">Batch</label>
                    <input id="batch" name="batch" type="text" value="{{ $filters['batch'] ?? '' }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. 2026-FALL" />
                </div>

                <div>
                    <label for="counsellor_id" class="block text-sm font-medium text-gray-700">Counsellor</label>
                    <select id="counsellor_id" name="counsellor_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <option value="">All Counsellors</option>
                        @foreach ($counsellors as $counsellor)
                            <option value="{{ $counsellor->id }}" @selected((string) $counsellor->id === (string) ($filters['assigned_counsellor_id'] ?? ''))>
                                {{ $counsellor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="source" class="block text-sm font-medium text-gray-700">Source</label>
                    <select id="source" name="source" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <option value="">All Sources</option>
                        @foreach ($leadSources as $source)
                            <option value="{{ $source->value }}" @selected($source->value === ($filters['source'] ?? ''))>
                                {{ $source->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($status->value === ($filters['status'] ?? ''))>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ $filters['from_date'] ?? '' }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ $filters['to_date'] ?? '' }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label for="score_min" class="block text-sm font-medium text-gray-700">Minimum Score</label>
                    <input id="score_min" name="score_min" type="number" min="0" max="100" value="{{ $filters['score_min'] ?? '' }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label for="score_max" class="block text-sm font-medium text-gray-700">Maximum Score</label>
                    <input id="score_max" name="score_max" type="number" min="0" max="100" value="{{ $filters['score_max'] ?? '' }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                </div>

                <div class="md:col-span-2 lg:col-span-4 flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Apply Filters
                    </button>
                    <a href="{{ route('crm.applications.list') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Applications</h2>

            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-medium text-gray-700">
                        AP-010 Bulk Actions
                        <span class="ml-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700" x-text="selected.length + ' selected'"></span>
                    </p>
                    <p class="text-xs text-gray-500">Select rows below and run an action.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <form method="POST" action="{{ route('crm.applications.bulk.status') }}" class="rounded-md border border-gray-200 bg-white p-3">
                        @csrf
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="flex-1 min-w-[180px]">
                                <label for="bulk_status" class="block text-xs font-medium text-gray-700">Bulk Status</label>
                                <select id="bulk_status" name="status" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                                    <option value="">Select status</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 min-w-[180px]">
                                <label for="bulk_reason" class="block text-xs font-medium text-gray-700">Reason</label>
                                <input id="bulk_reason" name="reason" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Optional note" />
                            </div>
                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700" :disabled="selected.length === 0">Update Status</button>
                        </div>
                        <template x-for="uuid in selected" :key="'status-' + uuid">
                            <input type="hidden" name="application_uuids[]" :value="uuid" />
                        </template>
                    </form>

                    <form method="POST" action="{{ route('crm.applications.bulk.assign') }}" class="rounded-md border border-gray-200 bg-white p-3">
                        @csrf
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="flex-1 min-w-[180px]">
                                <label for="bulk_counsellor" class="block text-xs font-medium text-gray-700">Assign Counsellor</label>
                                <select id="bulk_counsellor" name="counsellor_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                                    <option value="">Select counsellor</option>
                                    @foreach ($counsellors as $counsellor)
                                        <option value="{{ $counsellor->id }}">{{ $counsellor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700" :disabled="selected.length === 0">Assign</button>
                        </div>
                        <template x-for="uuid in selected" :key="'assign-' + uuid">
                            <input type="hidden" name="application_uuids[]" :value="uuid" />
                        </template>
                    </form>

                    <form method="POST" action="{{ route('crm.applications.bulk.communication') }}" class="rounded-md border border-gray-200 bg-white p-3 lg:col-span-2">
                        @csrf
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
                            <div class="md:col-span-2">
                                <label for="bulk_channel" class="block text-xs font-medium text-gray-700">Channel</label>
                                <select id="bulk_channel" name="channel" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                                    <option value="EMAIL">Email</option>
                                    <option value="SMS">SMS</option>
                                    <option value="WHATSAPP">WhatsApp</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="bulk_subject" class="block text-xs font-medium text-gray-700">Subject / Message Label</label>
                                <input id="bulk_subject" name="subject" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="For email" />
                            </div>
                            <div class="md:col-span-2">
                                <label for="bulk_message" class="block text-xs font-medium text-gray-700">Message</label>
                                <input id="bulk_message" name="message" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="For SMS" />
                            </div>
                            <div>
                                <label for="bulk_from_name" class="block text-xs font-medium text-gray-700">From Name</label>
                                <input id="bulk_from_name" name="from_name" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" value="Admissions Team" />
                            </div>
                            <div class="md:col-span-2">
                                <label for="bulk_from_email" class="block text-xs font-medium text-gray-700">From Email</label>
                                <input id="bulk_from_email" name="from_email" type="email" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" value="no-reply@example.test" />
                            </div>
                            <div>
                                <label for="bulk_dlt_template" class="block text-xs font-medium text-gray-700">DLT Template ID</label>
                                <input id="bulk_dlt_template" name="dlt_template_id" type="number" min="1" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                            </div>
                            <div class="md:col-span-2">
                                <label for="bulk_whatsapp_template_name" class="block text-xs font-medium text-gray-700">WhatsApp Template Name</label>
                                <input id="bulk_whatsapp_template_name" name="whatsapp_template_name" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                            </div>
                            <div class="md:col-span-6 flex justify-end">
                                <button type="submit" class="rounded-md bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700" :disabled="selected.length === 0">Send Communication</button>
                            </div>
                        </div>
                        <template x-for="uuid in selected" :key="'comm-' + uuid">
                            <input type="hidden" name="application_uuids[]" :value="uuid" />
                        </template>
                    </form>

                    <form method="POST" action="{{ route('crm.applications.bulk.export') }}" class="rounded-md border border-gray-200 bg-white p-3 lg:col-span-2">
                        @csrf
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label for="bulk_export_format" class="block text-xs font-medium text-gray-700">Export Format</label>
                                <select id="bulk_export_format" name="format" class="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    <option value="csv">CSV</option>
                                    <option value="json">JSON (flash)</option>
                                </select>
                            </div>
                            <button type="submit" class="rounded-md bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800" :disabled="selected.length === 0">Export Selected</button>
                        </div>
                        <template x-for="uuid in selected" :key="'export-' + uuid">
                            <input type="hidden" name="application_uuids[]" :value="uuid" />
                        </template>
                    </form>
                </div>

                @if (session('bulk_export_json'))
                    <div class="mt-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="mb-2 text-xs font-semibold text-slate-700">Last JSON Export</p>
                        <pre class="max-h-64 overflow-auto text-xs text-slate-700">{{ session('bulk_export_json') }}</pre>
                    </div>
                @endif
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600" @change="toggleAll($event)" />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Applicant</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Counsellor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Submitted</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($applications as $application)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        class="js-app-select h-4 w-4 rounded border-gray-300 text-indigo-600"
                                        value="{{ $application->uuid }}"
                                        @change="sync('{{ $application->uuid }}', $event.target.checked)"
                                    />
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ trim(($application->lead?->first_name ?? '').' '.($application->lead?->last_name ?? '')) ?: 'N/A' }}
                                    <div class="text-xs text-gray-500">{{ $application->lead?->email ?? 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $application->lead?->source?->label() ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $application->lead?->lead_score ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $application->status->label() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $application->assignedCounsellor?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($application->submitted_at)->format('d M Y') ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('crm.applications.show', ['application' => $application->uuid]) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No applications found for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</x-layouts.crm>
