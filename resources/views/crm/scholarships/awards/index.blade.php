{{-- BRD: CRM-FM-008 — Scholarship award approval queue --}}
<x-layouts.crm title="Scholarship Awards">
    <div class="space-y-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Scholarship Awards</h2>
                <p class="mt-1 text-sm text-gray-600">Counsellor → Manager → Finance approval chain.</p>
            </div>
            @can('scholarship.award.submit')
                <button type="button"
                    onclick="document.getElementById('submit-award-panel').classList.toggle('hidden')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    + Award Scholarship
                </button>
            @endcan
        </div>

        @can('scholarship.award.submit')
            <div id="submit-award-panel" class="hidden rounded-lg border border-indigo-200 bg-indigo-50 p-5">
                <h3 class="mb-4 text-sm font-semibold text-indigo-900">Submit New Scholarship Award</h3>
                <form method="POST" action="{{ route('crm.scholarships.awards.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Application</label>
                        <select name="application_uuid" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">— select applicant —</option>
                            @foreach ($applications as $app)
                                <option value="{{ $app->uuid }}" @selected(old('application_uuid') === $app->uuid)>
                                    {{ Str::upper(Str::of($app->uuid)->limit(8, '')) }} — {{ $app->lead?->full_name ?? 'Unknown' }}
                                </option>
                            @endforeach
                        </select>
                        @error('application_uuid')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Category</label>
                        <select name="scholarship_category_id" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">— select —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('scholarship_category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('scholarship_category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Amount</label>
                        <input type="number" name="amount" required min="0" step="0.01" placeholder="0.00"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            value="{{ old('amount') }}" />
                        @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-end sm:col-span-3">
                        <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Submit Award
                        </button>
                    </div>
                </form>
            </div>
        @endcan

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Award</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Application</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Stage</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($awards as $award)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ Str::of($award->uuid)->limit(8, '') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $award->category?->name }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ Str::of($award->application_uuid)->limit(8, '') }}</td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ number_format((float) $award->amount, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                        {{ $award->current_stage?->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($award->status?->isApproved())
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Approved</span>
                                    @elseif ($award->status?->isTerminal())
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">{{ $award->status?->label() }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">{{ $award->status?->label() }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if (! $award->status?->isTerminal())
                                        <form method="POST" action="{{ route('crm.scholarships.awards.decide', $award) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="decision" value="approve" />
                                            <button type="submit" class="btn-primary-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('crm.scholarships.awards.decide', $award) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="decision" value="reject" />
                                            <input type="hidden" name="reason" value="Rejected from queue" />
                                            <button type="submit" class="btn-secondary-sm">Reject</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Closed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-16 text-center text-sm text-gray-500">No scholarship awards yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($awards->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">{{ $awards->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.crm>
