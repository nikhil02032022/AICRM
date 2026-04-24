<x-layouts.crm title="Data Import">
    <div class="space-y-6" x-data="dataImport()">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Import</h1>
                <p class="mt-1 text-sm text-gray-500">Import leads, applications or contacts from CSV or Excel files</p>
            </div>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Import form --}}
        <form
            method="POST"
            action="{{ route('crm.admin.data-import.upload') }}"
            enctype="multipart/form-data"
            class="space-y-5"
        >
            @csrf

            {{-- Step 1: Entity type --}}
            <div class="card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">1</span>
                    <h2 class="text-base font-semibold text-gray-800">Select Entity Type</h2>
                </div>
                <div class="form-group">
                    <label for="entity" class="form-label">Import As <span class="text-red-500">*</span></label>
                    <select
                        id="entity"
                        name="entity"
                        x-model="entity"
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

                {{-- Template download hint --}}
                <div x-show="entity !== ''" x-cloak class="mt-3 rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700">
                    <strong>Tip:</strong> Download the import template for
                    <span class="font-semibold" x-text="entity"></span>
                    to ensure your file has the correct column headers.
                </div>
            </div>

            {{-- Step 2: File upload --}}
            <div class="card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">2</span>
                    <h2 class="text-base font-semibold text-gray-800">Upload File</h2>
                </div>

                {{-- Drop zone --}}
                <div
                    class="relative rounded-xl border-2 border-dashed transition-colors"
                    :class="dragover ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300'"
                    @dragover.prevent="dragover = true"
                    @dragleave.prevent="dragover = false"
                    @drop.prevent="onDrop($event)"
                >
                    <input
                        id="file"
                        type="file"
                        name="file"
                        accept=".csv,.xlsx,.xls"
                        @change="onFileChange($event)"
                        class="absolute inset-0 cursor-pointer opacity-0 h-full w-full"
                        aria-label="Upload file"
                    >
                    <div class="flex flex-col items-center justify-center gap-3 py-12 text-center pointer-events-none">
                        <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                <span x-show="!fileName">Drag and drop your file here, or click to browse</span>
                                <span x-show="fileName" x-text="fileName" class="text-indigo-600 font-semibold"></span>
                            </p>
                            <p class="mt-1 text-xs text-gray-400">Supports .csv and .xlsx files</p>
                        </div>
                    </div>
                </div>

                @error('file')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-5 flex items-center gap-3">
                    <button type="submit" class="btn-primary" :disabled="!fileName || !entity">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Upload &amp; Import
                    </button>
                    <span class="text-xs text-gray-400">Max file size: 10 MB</span>
                </div>
            </div>
        </form>

        {{-- Results / Errors section --}}
        @if(isset($importErrors) && count($importErrors))
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                    <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-800">Import Errors ({{ count($importErrors) }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th w-24">Row</th>
                                <th class="table-th">Error Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white">
                            @foreach($importErrors as $err)
                                <tr>
                                    <td class="table-td font-mono text-sm text-gray-600">{{ $err['row'] ?? '—' }}</td>
                                    <td class="table-td text-sm text-red-600">{{ $err['message'] ?? $err }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(isset($importSuccess) && $importSuccess > 0)
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                Successfully imported <strong>{{ $importSuccess }}</strong> record(s).
            </div>
        @endif

    </div>

    <script>
    function dataImport() {
        return {
            entity: '{{ old('entity', '') }}',
            dragover: false,
            fileName: '',

            onFileChange(e) {
                const file = e.target.files[0];
                this.fileName = file ? file.name : '';
            },

            onDrop(e) {
                this.dragover = false;
                const file = e.dataTransfer.files[0];
                if (!file) return;
                this.fileName = file.name;
                // Transfer to the real file input
                const input = document.getElementById('file');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            },
        };
    }
    </script>
</x-layouts.crm>
