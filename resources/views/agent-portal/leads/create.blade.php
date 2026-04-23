{{-- BRD: CRM-AG-003 — Agent portal: submit a new lead --}}
<x-layouts.agent-portal-app title="Submit Lead">
    <div class="mx-auto max-w-xl">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('agent-portal.leads.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Submit a Lead</h1>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                Please correct the errors below.
            </div>
        @endif

        <form method="POST" action="{{ route('agent-portal.leads.store') }}" class="space-y-5">
            @csrf

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Student Details</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500
                                      @error('first_name') border-red-400 @enderror">
                        @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500
                                      @error('last_name') border-red-400 @enderror">
                        @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500
                                  @error('email') border-red-400 @enderror">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Mobile <span class="text-red-500">*</span></label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" required
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500
                                  @error('mobile') border-red-400 @enderror">
                    @error('mobile')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Programme of Interest</label>
                    <select name="programme_id" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Select (optional)</option>
                        @foreach($programmes as $id => $name)
                            <option value="{{ $id }}" @selected(old('programme_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" rows="3" placeholder="Any additional context about this student…"
                              class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- DPDP consent --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="consent_given" value="1" required
                           class="mt-0.5 h-4 w-4 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                    <span>
                        I confirm that the student has given explicit consent for their information to be shared with
                        the institution for admissions purposes, in accordance with the DPDP Act 2023.
                    </span>
                </label>
                @error('consent_given')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('agent-portal.leads.index') }}"
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    Submit Lead
                </button>
            </div>
        </form>
    </div>
</x-layouts.agent-portal-app>
