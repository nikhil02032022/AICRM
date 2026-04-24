<x-layouts.crm title="Data Export">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Export</h1>
                <p class="mt-1 text-sm text-gray-500">Export records to Excel for reporting or archival</p>
            </div>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Info banner --}}
        <div class="flex items-start gap-3 rounded-lg bg-blue-50 border border-blue-100 px-4 py-3">
            <svg class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-blue-700">Exports will be downloaded as Excel (.xlsx) files. Large exports may take a few seconds to generate.</p>
        </div>

        {{-- Export form --}}
        <form method="POST" action="{{ route('crm.admin.data-export.export') }}">
            @csrf

            <div class="card p-6 space-y-5">
                <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Export Options</h2>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Entity --}}
                    <div class="form-group lg:col-span-3">
                        <label for="entity" class="form-label">Export Type <span class="text-red-500">*</span></label>
                        <select
                            id="entity"
                            name="entity"
                            required
                            class="form-input max-w-xs @error('entity') border-red-300 @enderror"
                        >
                            <option value="">— Select type —</option>
                            <option value="leads"        @selected(old('entity') === 'leads')>Leads</option>
                            <option value="applications" @selected(old('entity') === 'applications')>Applications</option>
                            <option value="contacts"     @selected(old('entity') === 'contacts')>Contacts</option>
                        </select>
                        @error('entity')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date from --}}
                    <div class="form-group">
                        <label for="date_from" class="form-label">Date From</label>
                        <input
                            id="date_from"
                            type="date"
                            name="date_from"
                            value="{{ old('date_from') }}"
                            class="form-input @error('date_from') border-red-300 @enderror"
                        >
                        @error('date_from')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date to --}}
                    <div class="form-group">
                        <label for="date_to" class="form-label">Date To</label>
                        <input
                            id="date_to"
                            type="date"
                            name="date_to"
                            value="{{ old('date_to') }}"
                            class="form-input @error('date_to') border-red-300 @enderror"
                        >
                        @error('date_to')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                    <button type="submit" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download Export
                    </button>
                    <span class="text-xs text-gray-400">File will download as .xlsx</span>
                </div>
            </div>
        </form>

        {{-- Recent exports (optional, shown if passed from controller) --}}
        @if(!empty($recentExports))
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Recent Exports</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Type</th>
                                <th class="table-th">Date Range</th>
                                <th class="table-th">Generated By</th>
                                <th class="table-th">Created</th>
                                <th class="table-th text-right">Download</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white">
                            @foreach($recentExports as $export)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="table-td font-medium text-gray-900 capitalize">{{ $export->entity }}</td>
                                    <td class="table-td text-gray-600 text-sm">
                                        {{ optional($export->date_from)->format('d M Y') ?? '—' }}
                                        @if($export->date_to) — {{ $export->date_to->format('d M Y') }} @endif
                                    </td>
                                    <td class="table-td text-gray-600">{{ optional($export->user)->name ?? 'System' }}</td>
                                    <td class="table-td text-gray-500 text-sm">{{ $export->created_at->format('d M Y, H:i') }}</td>
                                    <td class="table-td text-right">
                                        <a
                                            href="{{ $export->download_url }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 shadow-sm hover:bg-indigo-100 transition-colors"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            .xlsx
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-layouts.crm>
