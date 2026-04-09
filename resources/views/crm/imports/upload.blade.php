<x-layouts.crm title="Upload Leads">
    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Page header --}}
        <div>
            <a href="{{ route('crm.imports.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Import History
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Upload Lead CSV / Excel</h1>
            <p class="mt-1 text-sm text-gray-500">Max 5 MB · CSV or .xlsx · Up to ~5,000 leads per file</p>
        </div>

        {{-- Template download --}}
        <div class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3.5 text-sm text-indigo-800">
            <svg class="h-5 w-5 flex-shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            <span>Download the <a href="{{ asset('templates/lead-import-template.csv') }}" class="font-semibold underline hover:no-underline">CSV template</a> to ensure correct column format.</span>
        </div>

        {{-- Upload form --}}
        <form method="POST" action="{{ route('crm.imports.store') }}"
              enctype="multipart/form-data"
              class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5"
              x-data="{ fileName: '', dragging: false }">
            @csrf

            {{-- Validation errors --}}
            @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Channel selector --}}
            <div>
                <label for="channel" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Source Channel <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select id="channel" name="channel" required
                        class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 @error('channel') border-red-500 @enderror">
                    <option value="">Select channel…</option>
                    @foreach($channelOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('channel') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('channel')
                <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Drag & drop file upload --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                    File <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <div
                    class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 transition-colors"
                    :class="dragging ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; fileName = $event.dataTransfer.files[0]?.name ?? ''; $refs.fileInput.files = $event.dataTransfer.files"
                    @click="$refs.fileInput.click()"
                    role="button"
                    tabindex="0"
                    aria-label="Upload CSV or Excel file"
                >
                    <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <p class="mt-3 text-sm text-gray-600">
                        <span x-show="!fileName">Drag & drop or <span class="font-semibold text-indigo-600">browse</span></span>
                        <span x-show="fileName" class="font-semibold text-gray-900" x-text="fileName"></span>
                    </p>
                    <p class="mt-1 text-xs text-gray-400">.csv or .xlsx · max 5 MB</p>
                    <input
                        type="file"
                        name="file"
                        accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="sr-only"
                        x-ref="fileInput"
                        @change="fileName = $event.target.files[0]?.name ?? ''"
                        required
                        aria-required="true"
                    >
                </div>
                @error('file')
                <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- DPDP: CRM-CR-001 — Consent attestation mandatory --}}
            <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <input type="checkbox" id="consent_attestation" name="consent_attestation" value="1"
                           required
                           class="mt-0.5 h-4 w-4 flex-shrink-0 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 @error('consent_attestation') border-red-500 @enderror"
                           aria-required="true">
                    <label for="consent_attestation" class="cursor-pointer text-xs text-gray-700 leading-relaxed">
                        <span class="font-semibold text-gray-900">I confirm that explicit consent has been obtained</span>
                        from all individuals in this file for the use of their personal data for admissions communications,
                        in accordance with the <span class="font-medium">DPDP Act 2023</span>.
                        <span class="text-red-500" aria-hidden="true"> *</span>
                    </label>
                </div>
                @error('consent_attestation')
                <p class="mt-1.5 ml-7 text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Column reference --}}
            <details class="rounded-lg border border-gray-200 bg-gray-50 text-sm">
                <summary class="cursor-pointer px-4 py-3 font-medium text-gray-700 select-none">Expected CSV columns</summary>
                <div class="overflow-x-auto px-4 pb-4">
                    <table class="mt-2 min-w-full text-xs">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="py-2 pr-4 text-left font-semibold text-gray-600">Column</th>
                                <th class="py-2 pr-4 text-left font-semibold text-gray-600">Required</th>
                                <th class="py-2 text-left font-semibold text-gray-600">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach([
                                ['first_name', true,  'Or use a single "name" column'],
                                ['last_name',  false, 'Required if first_name is used'],
                                ['mobile',     true,  '10-digit Indian number (6–9 prefix)'],
                                ['email',      false, ''],
                                ['source',     false, 'Defaults to selected channel above'],
                                ['city',       false, ''],
                                ['state',      false, ''],
                                ['programme_interest', false, 'Free text — matched to programme list'],
                                ['notes',      false, ''],
                                ['utm_source', false, ''],
                                ['utm_campaign', false, ''],
                            ] as [$col, $req, $note])
                            <tr>
                                <td class="py-1.5 pr-4 font-mono text-gray-800">{{ $col }}</td>
                                <td class="py-1.5 pr-4">
                                    @if($req)
                                    <span class="text-red-600 font-semibold">Yes</span>
                                    @else
                                    <span class="text-gray-400">No</span>
                                    @endif
                                </td>
                                <td class="py-1.5 text-gray-500">{{ $note }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>

            <button type="submit"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                Start Import
            </button>
        </form>

    </div>
</x-layouts.crm>
