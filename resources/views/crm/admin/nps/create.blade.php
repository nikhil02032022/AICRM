{{-- BRD: CRM-AL-004 — Manual NPS snapshot entry --}}
<x-layouts.crm title="Add NPS Entry">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('crm.admin.nps.index') }}" class="hover:text-indigo-600">Alumni NPS</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">Add Entry</span>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('crm.admin.nps.store') }}" class="max-w-2xl space-y-6">
        @csrf

        @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-800">Please fix the following errors:</p>
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-sm text-red-700">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- Context --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-gray-900">Survey Context</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="academic_year_id" class="mb-1.5 block text-sm font-medium text-gray-700">Academic Year <span class="text-red-500">*</span></label>
                    <select id="academic_year_id" name="academic_year_id" required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('academic_year_id') border-red-500 @enderror">
                        <option value="">Select academic year…</option>
                        @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                            {{ $year->label }}
                        </option>
                        @endforeach
                    </select>
                    @error('academic_year_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="programme_id" class="mb-1.5 block text-sm font-medium text-gray-700">Programme <span class="text-gray-400 text-xs">(optional)</span></label>
                    <select id="programme_id" name="programme_id"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('programme_id') border-red-500 @enderror">
                        <option value="">All Programmes</option>
                        @foreach($programmes as $programme)
                        <option value="{{ $programme->id }}" {{ old('programme_id') == $programme->id ? 'selected' : '' }}>
                            {{ $programme->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('programme_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="survey_date" class="mb-1.5 block text-sm font-medium text-gray-700">Survey Date <span class="text-red-500">*</span></label>
                    <input type="date" id="survey_date" name="survey_date" value="{{ old('survey_date') }}" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('survey_date') border-red-500 @enderror">
                    @error('survey_date')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Percentages --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Response Distribution</h2>
                <div id="pct-preview" class="hidden items-center gap-2 rounded-lg bg-gray-50 px-3 py-1.5 text-sm">
                    <span class="text-xs text-gray-500">NPS Preview:</span>
                    <span id="nps-preview-value" class="text-base font-bold tabular-nums text-gray-800">—</span>
                </div>
            </div>
            <p class="mb-4 text-xs text-gray-500">The three percentages must add up to exactly 100%.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label for="promoters_pct" class="mb-1.5 block text-sm font-medium text-gray-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-green-500 mr-1"></span>
                        Promoters (%) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="promoters_pct" name="promoters_pct"
                           value="{{ old('promoters_pct') }}" min="0" max="100" step="0.01" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('promoters_pct') border-red-500 @enderror"
                           placeholder="e.g. 65">
                    @error('promoters_pct')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-gray-400">Score 9–10</p>
                </div>
                <div>
                    <label for="neutrals_pct" class="mb-1.5 block text-sm font-medium text-gray-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-gray-400 mr-1"></span>
                        Neutrals (%) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="neutrals_pct" name="neutrals_pct"
                           value="{{ old('neutrals_pct') }}" min="0" max="100" step="0.01" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('neutrals_pct') border-red-500 @enderror"
                           placeholder="e.g. 20">
                    @error('neutrals_pct')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-gray-400">Score 7–8</p>
                </div>
                <div>
                    <label for="detractors_pct" class="mb-1.5 block text-sm font-medium text-gray-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-red-500 mr-1"></span>
                        Detractors (%) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="detractors_pct" name="detractors_pct"
                           value="{{ old('detractors_pct') }}" min="0" max="100" step="0.01" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('detractors_pct') border-red-500 @enderror"
                           placeholder="e.g. 15">
                    @error('detractors_pct')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-gray-400">Score 0–6</p>
                </div>
            </div>

            {{-- Sum validation feedback --}}
            <div id="sum-feedback" class="mt-3 hidden rounded-lg px-3 py-2 text-xs font-medium"></div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('crm.admin.nps.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" id="submit-btn" class="btn-primary">Save NPS Entry</button>
        </div>
    </form>

    @push('scripts')
    <script>
    (function () {
        const promoters  = document.getElementById('promoters_pct');
        const neutrals   = document.getElementById('neutrals_pct');
        const detractors = document.getElementById('detractors_pct');
        const feedback   = document.getElementById('sum-feedback');
        const preview    = document.getElementById('pct-preview');
        const previewVal = document.getElementById('nps-preview-value');
        const submitBtn  = document.getElementById('submit-btn');

        function update() {
            const p = parseFloat(promoters.value) || 0;
            const n = parseFloat(neutrals.value)  || 0;
            const d = parseFloat(detractors.value) || 0;
            const sum = p + n + d;

            if (p === 0 && n === 0 && d === 0) {
                feedback.classList.add('hidden');
                preview.classList.add('hidden');
                submitBtn.disabled = false;
                return;
            }

            const ok = Math.abs(sum - 100) <= 0.01;

            feedback.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            if (ok) {
                feedback.classList.add('bg-green-50', 'text-green-700');
                feedback.textContent = 'Total: 100% ✓';
            } else {
                feedback.classList.add('bg-red-50', 'text-red-700');
                feedback.textContent = 'Total: ' + sum.toFixed(2) + '% — must equal 100%';
            }

            const nps = Math.round(p - d);
            preview.classList.remove('hidden');
            preview.classList.add('flex');
            previewVal.textContent = (nps >= 0 ? '+' : '') + nps;
            previewVal.className = 'text-base font-bold tabular-nums ' + (nps > 50 ? 'text-green-600' : (nps >= 0 ? 'text-yellow-500' : 'text-red-500'));

            submitBtn.disabled = !ok;
        }

        [promoters, neutrals, detractors].forEach(el => el.addEventListener('input', update));
        update();
    })();
    </script>
    @endpush
</x-layouts.crm>
