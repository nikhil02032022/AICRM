<div class="flex flex-col h-[70vh]" wire:poll.5s="refreshMessages">

    {{-- Message thread --}}
    <div class="flex-1 overflow-y-auto space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200" id="thread-scroll">
        @forelse ($this->messages as $msg)
        <div @class(['flex', 'justify-end' => $msg->direction->value === 'OUTBOUND', 'justify-start' => $msg->direction->value === 'INBOUND'])>
            <div @class([
                'max-w-sm px-3 py-2 rounded-xl text-sm shadow-sm',
                'bg-green-500 text-white rounded-br-none' => $msg->direction->value === 'OUTBOUND',
                'bg-white text-gray-800 rounded-bl-none border border-gray-100' => $msg->direction->value === 'INBOUND',
            ])>
                <p>{{ $msg->body }}</p>
                <p @class([
                    'text-xs mt-1',
                    'text-green-100' => $msg->direction->value === 'OUTBOUND',
                    'text-gray-400' => $msg->direction->value === 'INBOUND',
                ])>{{ $msg->created_at->format('H:i') }} · {{ $msg->status->value }}</p>
            </div>
        </div>
        @empty
        <p class="text-center text-sm text-gray-400 py-6">No messages yet. Send the first message below.</p>
        @endforelse
    </div>

    {{-- Compose --}}
    @can('crm.communication.send')
    <div class="mt-3 flex gap-2" x-data>
        <textarea
            wire:model="messageText"
            rows="2"
            placeholder="Type a message…"
            class="form-input flex-1 resize-none"
            maxlength="4096"
            x-on:keydown.enter.prevent="if(!$event.shiftKey) $wire.sendMessage()"
            aria-label="Message text"
        ></textarea>
        <button
            wire:click="sendMessage"
            wire:loading.attr="disabled"
            class="btn-primary self-end px-4"
            aria-label="Send message"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
        </button>
    </div>
    @endcan

    <script>
    document.addEventListener('livewire:navigated', () => scrollThread());
    document.addEventListener('message-sent', () => scrollThread());
    function scrollThread() {
        const el = document.getElementById('thread-scroll');
        if (el) el.scrollTop = el.scrollHeight;
    }
    scrollThread();
    </script>
</div>
