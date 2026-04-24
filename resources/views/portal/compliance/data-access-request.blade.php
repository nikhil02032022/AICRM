<x-layouts.crm title="Request My Data">
    <div class="space-y-6 max-w-xl mx-auto">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Request a Copy of Your Data</h1>
            <p class="mt-1 text-sm text-gray-500">Under the DPDP Act 2023, you have the right to access your personal data.</p>
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

        {{-- Info Card --}}
        <div class="rounded-lg bg-blue-50 border border-blue-200 text-blue-800 px-4 py-4 text-sm">
            <p class="font-semibold mb-1">What data will be included?</p>
            <ul class="list-disc list-inside space-y-0.5 text-blue-700">
                <li>Personal profile information</li>
                <li>Application history and status</li>
                <li>Communication records</li>
                <li>Consent records and opt-out history</li>
            </ul>
        </div>

        {{-- Form Card --}}
        <div class="card p-6">
            <form method="POST" action="{{ route('crm.portal.data-access.store') }}" class="space-y-5">
                @csrf

                {{-- Delivery Method --}}
                <div>
                    <fieldset>
                        <legend class="block text-sm font-medium text-gray-700 mb-3">How would you like to receive your data?</legend>
                        <div class="space-y-3">
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <div class="relative flex items-center justify-center mt-0.5">
                                    <input type="radio"
                                        name="delivery_method"
                                        value="email"
                                        @checked(old('delivery_method', 'email') === 'email')
                                        class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 group-hover:text-indigo-700">Email</span>
                                    <span class="block text-xs text-gray-500">We'll send your data to your registered email address as a secure download link.</span>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <div class="relative flex items-center justify-center mt-0.5">
                                    <input type="radio"
                                        name="delivery_method"
                                        value="download"
                                        @checked(old('delivery_method') === 'download')
                                        class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 group-hover:text-indigo-700">Download</span>
                                    <span class="block text-xs text-gray-500">A downloadable file will be prepared and made available in your portal.</span>
                                </div>
                            </label>
                        </div>
                        @error('delivery_method')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

                {{-- Submit --}}
                <div class="pt-2 border-t border-gray-100">
                    <button type="submit" class="btn-primary w-full justify-center">Submit Request</button>
                    <p class="mt-2 text-xs text-center text-gray-400">Your request will be processed within 30 days as required by the DPDP Act 2023.</p>
                </div>
            </form>
        </div>

    </div>
</x-layouts.crm>
