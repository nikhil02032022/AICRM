<x-layouts.crm>
    <x-slot:header>Application List</x-slot:header>

    <div class="space-y-6">
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

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
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
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No applications found for current filters.</td>
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
