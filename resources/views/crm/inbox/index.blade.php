<x-layouts.crm title="Unified Inbox">
    <div class="space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">Unified Inbox</h1>
            <p class="mt-1 text-sm text-gray-500">All inbound messages — WhatsApp, Email, SMS · BRD CC-021</p>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        {{-- Livewire component handles tabs and polling --}}
        @livewire('crm.communication.unified-inbox')

    </div>
</x-layouts.crm>
