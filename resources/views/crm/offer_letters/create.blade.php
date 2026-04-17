@extends('layouts.app')

@section('title', 'Generate Offer Letter')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Generate Offer Letter</h1>

    <form action="{{ route('crm.applications.offers.store', $application->uuid) }}" method="POST" class="bg-white rounded-lg border border-gray-200 p-6">
        @csrf

        <div class="space-y-6">
            <!-- Application Information -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">Application Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Applicant</span>
                        <p class="font-semibold">{{ $application->lead->full_name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Programme</span>
                        <p class="font-semibold">{{ $application->programme->name }}</p>
                    </div>
                </div>
            </div>

            <!-- Expiry Configuration -->
            <div>
                <label for="expires_in_days" class="block text-sm font-medium text-gray-900 mb-2">
                    Offer Expiry (Days)
                </label>
                <input type="number" name="expires_in_days" id="expires_in_days" min="1" max="365" value="30" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <p class="text-sm text-gray-600 mt-1">Default: 30 days</p>
            </div>

            <!-- Reason -->
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-900 mb-2">
                    Reason (Optional)
                </label>
                <textarea name="reason" id="reason" rows="3" placeholder="e.g., Merit-based selection, entrance exam result..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <!-- Conditional Offer (AP-014) -->
            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="conditional" id="conditional" value="1" class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2 text-sm text-gray-900 font-medium">This is a <span class="font-semibold">conditional offer</span> (requires document checklist)</span>
                </label>
            </div>

            <!-- Document Checklist (AP-014) -->
            <div id="conditional-docs-section" class="mt-4 hidden">
                <label for="required_documents" class="block text-sm font-medium text-gray-900 mb-2">
                    Required Documents for Acceptance
                </label>
                <div class="grid grid-cols-2 gap-2">
                    @php
                        // Example document types; replace with dynamic list from Document Management module if available
                        $docTypes = [
                            'marksheet' => 'Marksheet',
                            'id_proof' => 'ID Proof',
                            'photo' => 'Photograph',
                            'address_proof' => 'Address Proof',
                            'caste_certificate' => 'Caste Certificate',
                            'income_certificate' => 'Income Certificate',
                        ];
                    @endphp
                    @foreach ($docTypes as $key => $label)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="required_documents[]" value="{{ $key }}" class="form-checkbox h-4 w-4 text-blue-600">
                            <span class="ml-2 text-gray-800">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-2">Applicant must submit and verify all selected documents before acceptance is allowed.</p>
            </div>

            <!-- Submit -->
            <div class="flex gap-2 justify-end mt-6">
                <a href="{{ route('crm.applications.offers.index', $application->uuid) }}" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Generate Offer Letter
                </button>
            </div>
        </form>
        <script>
            // Alpine.js-style toggle for conditional offer document checklist
            document.addEventListener('DOMContentLoaded', function () {
                const conditionalCheckbox = document.getElementById('conditional');
                const docsSection = document.getElementById('conditional-docs-section');
                if (conditionalCheckbox && docsSection) {
                    function toggleDocsSection() {
                        docsSection.classList.toggle('hidden', !conditionalCheckbox.checked);
                    }
                    conditionalCheckbox.addEventListener('change', toggleDocsSection);
                    toggleDocsSection();
                }
            });
        </script>
        </div>
    </form>
</div>
@endsection
