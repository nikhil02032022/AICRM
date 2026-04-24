<x-layouts.crm title="Request Data Erasure">
    <div class="space-y-6 max-w-xl mx-auto">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Request Data Erasure</h1>
            <p class="mt-1 text-sm text-gray-500">Under the DPDP Act 2023, you have the right to request erasure of your personal data.</p>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Warning Callout --}}
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-4 text-sm">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-semibold text-red-800">This action is irreversible.</p>
                    <p class="mt-1 text-red-700">All your personal data will be permanently anonymised after a 30-day period. Your application history, contact details, and all associated records will be erased.</p>
                </div>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="card p-6">
            <form method="POST" action="{{ route('crm.portal.erasure.store') }}" class="space-y-5">
                @csrf

                {{-- Reason --}}
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                        Reason for Erasure <span class="text-gray-400 text-xs font-normal">(optional)</span>
                    </label>
                    <textarea
                        id="reason"
                        name="reason"
                        rows="4"
                        placeholder="Please let us know why you'd like your data erased (optional)..."
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('reason') border-red-400 @enderror">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirmation Checkbox --}}
                <div>
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox"
                            name="confirm"
                            value="1"
                            required
                            @checked(old('confirm'))
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 @error('confirm') border-red-400 @enderror">
                        <span class="text-sm text-gray-700 leading-snug group-hover:text-gray-900">
                            I understand my data will be permanently deleted after 30 days and this action cannot be undone.
                        </span>
                    </label>
                    @error('confirm')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="pt-2 border-t border-gray-100">
                    <button type="submit"
                        onclick="return confirm('Are you absolutely sure? This will permanently erase all your personal data after 30 days.')"
                        class="w-full justify-center inline-flex items-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                        Submit Erasure Request
                    </button>
                    <p class="mt-2 text-xs text-center text-gray-400">Your request will be processed within 30 days as required by the DPDP Act 2023.</p>
                </div>
            </form>
        </div>

    </div>
</x-layouts.crm>
