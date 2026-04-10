<x-layouts.crm :title="'WhatsApp: ' . $conversation->lead?->name">
    <div class="space-y-4">

        <div class="flex items-center gap-3">
            <a href="{{ route('crm.communication.whatsapp.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $conversation->lead?->name ?? 'Unknown' }}</h1>
                <p class="text-sm text-gray-500">{{ $conversation->status->value }} · Assigned to {{ $conversation->assignedCounsellor?->name ?? 'Unassigned' }}</p>
            </div>
        </div>

        {{-- Livewire real-time thread — polls every 5s --}}
        @livewire('crm.communication.conversation-thread', ['conversationUuid' => $conversation->uuid])

    </div>
</x-layouts.crm>
