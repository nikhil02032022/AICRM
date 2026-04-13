<x-layouts.crm title="Schedule Follow-up Session">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Schedule Follow-up Session</h1>
            <p class="mt-1 text-sm text-gray-600">Plan the next counsellor interaction for {{ $lead->name }}.</p>
        </div>

        @if ($followUpPrompt)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-800">Post-call prompt: {{ $followUpPrompt['disposition_label'] ?? 'Disposition' }}</p>
                <p class="mt-1 text-sm text-amber-700">This call outcome requires the next follow-up to be scheduled now.</p>
            </div>
        @endif

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('crm.leads.sessions.store', $lead->uuid) }}" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <label for="counsellor_id" class="block text-sm font-medium text-gray-700">Counsellor <span class="text-red-600">*</span></label>
                        <select id="counsellor_id" name="counsellor_id" class="mt-1.5 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                            <option value="">Select counsellor</option>
                            @foreach ($counsellors as $counsellor)
                                <option value="{{ $counsellor->id }}" {{ (string) old('counsellor_id', $counsellorId) === (string) $counsellor->id ? 'selected' : '' }}>{{ $counsellor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="session_type" class="block text-sm font-medium text-gray-700">Session Type <span class="text-red-600">*</span></label>
                        <select id="session_type" name="session_type" class="mt-1.5 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                            <option value="initial">Initial Counselling</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="group">Group Session</option>
                            <option value="walk_in">Walk-in</option>
                        </select>
                    </div>

                    <div>
                        <label for="scheduled_at" class="block text-sm font-medium text-gray-700">Schedule At <span class="text-red-600">*</span></label>
                        <input id="scheduled_at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}" class="mt-1.5 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                    </div>

                    <div>
                        <label for="mode" class="block text-sm font-medium text-gray-700">Mode <span class="text-red-600">*</span></label>
                        <select id="mode" name="mode" class="mt-1.5 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                            <option value="phone">Phone</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="pre_session_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="pre_session_notes" name="pre_session_notes" rows="3" class="mt-1.5 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">{{ old('pre_session_notes') }}</textarea>
                    </div>

                    <div class="md:col-span-2 flex items-center gap-2">
                        <button type="submit" class="btn-primary-sm">Schedule Follow-up</button>
                        <a href="{{ route('crm.leads.show', $lead->uuid) }}" class="btn-secondary-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.crm>
