<x-layouts.crm title="Record Call Outcome">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Record Call Outcome</h1>
                <p class="mt-1 text-sm text-gray-500">Save disposition and optionally attach a call transcript for AI summarisation</p>
            </div>
            <a href="{{ route('crm.communication.voice.index') }}" class="btn-secondary-sm">Cancel</a>
        </div>

        @if(session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if(session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif

        @if($errors->any())
            <x-alert type="error" message="Please correct the errors below." />
        @endif

        <div class="card">
            <div class="card-body">
                <form
                    method="POST"
                    action="{{ route('crm.communication.voice.calls.disposition', $callLog->uuid) }}"
                    class="space-y-5"
                >
                    @csrf

                    {{-- Disposition --}}
                    <div>
                        <label for="disposition" class="block text-sm font-medium text-gray-700">Disposition <span class="text-red-500">*</span></label>
                        <select
                            id="disposition"
                            name="disposition"
                            class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            required
                        >
                            <option value="">— Select disposition —</option>
                            @foreach($dispositionOptions as $code => $label)
                                <option value="{{ $code }}" @selected(old('disposition') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('disposition')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="disposition_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea
                            id="disposition_notes"
                            name="disposition_notes"
                            rows="3"
                            maxlength="1000"
                            class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            placeholder="Optional — if left blank and a transcript is provided, the AI summary sentence will be used."
                        >{{ old('disposition_notes') }}</textarea>
                        @error('disposition_notes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Duration --}}
                    <div>
                        <label for="duration_seconds" class="block text-sm font-medium text-gray-700">Duration (seconds)</label>
                        <input
                            type="number"
                            id="duration_seconds"
                            name="duration_seconds"
                            min="0"
                            value="{{ old('duration_seconds') }}"
                            class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                        >
                        @error('duration_seconds')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Transcript (optional) — BRD: CRM-AI-007 --}}
                    <div>
                        <label for="transcript_text" class="block text-sm font-medium text-gray-700">
                            Call Transcript
                            <span class="ml-1 text-xs font-normal text-gray-400">(optional — paste raw transcript text for AI summarisation)</span>
                        </label>
                        <div class="relative mt-1">
                            <textarea
                                id="transcript_text"
                                name="transcript_text"
                                rows="8"
                                maxlength="50000"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                placeholder="Paste the call transcript here…"
                                oninput="document.getElementById('transcript_char_count').textContent = this.value.length"
                            >{{ old('transcript_text') }}</textarea>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <p class="text-xs text-gray-400">
                                <span id="transcript_char_count">0</span> / 50,000 characters
                            </p>
                            <p class="text-xs text-amber-600">
                                Avoid pasting Aadhaar numbers, full bank details, or other sensitive documents.
                            </p>
                        </div>
                        @error('transcript_text')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('crm.communication.voice.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Outcome</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.crm>
