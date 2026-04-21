<x-layouts.portal-app title="Chat" :applicant="$applicant">
    <x-slot:header>
        <h1 class="text-lg font-semibold text-gray-800">Chat with your Counsellor</h1>
    </x-slot:header>

    {{-- Flash messages --}}
    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Chat thread --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden flex flex-col"
         style="min-height: 480px;">

        {{-- Messages area --}}
        <div class="flex-1 px-5 py-5 space-y-4 overflow-y-auto" id="chat-thread">
            @if ($messages->isEmpty())
                <div class="flex flex-col items-center justify-center h-full py-16 text-center">
                    <svg class="h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <p class="text-sm text-gray-400 font-medium">No messages yet</p>
                    <p class="text-xs text-gray-400 mt-1">Send a message to your counsellor below.</p>
                </div>
            @else
                @foreach ($messages as $msg)
                    @php
                        $fromApplicant = $msg->isFromApplicant();
                        $time          = $msg->created_at->format('d M, g:i A');
                    @endphp

                    <div class="flex {{ $fromApplicant ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-sm lg:max-w-md {{ $fromApplicant
                                ? 'portal-btn-primary rounded-2xl rounded-br-sm'
                                : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm' }}
                             px-4 py-2.5 shadow-sm">
                            @if (! $fromApplicant)
                                <p class="text-xs font-semibold text-gray-500 mb-1">Counsellor</p>
                            @endif
                            <p class="text-sm whitespace-pre-wrap break-words">{{ $msg->body }}</p>
                            <p class="mt-1 text-xs {{ $fromApplicant ? 'text-white/60' : 'text-gray-400' }} text-right">
                                {{ $time }}
                                @if ($fromApplicant)
                                    &nbsp;·&nbsp;Sent
                                @elseif ($msg->isReadByApplicant())
                                    &nbsp;·&nbsp;Read
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Compose area --}}
        <div class="border-t border-gray-200 bg-gray-50 px-5 py-4">
            <form method="POST" action="{{ route('portal.chat.store') }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="body" class="sr-only">Message</label>
                    <textarea
                        id="body"
                        name="body"
                        rows="2"
                        maxlength="2000"
                        placeholder="Type a message…"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
                               text-gray-900 placeholder-gray-400 shadow-sm resize-none
                               focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400
                               @error('body') border-red-400 @enderror"
                    >{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="shrink-0 inline-flex items-center gap-1.5 rounded-lg portal-btn-primary
                               px-4 py-2.5 text-sm font-medium transition-opacity disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Send
                </button>
            </form>
        </div>
    </div>

</x-layouts.portal-app>

<script>
    // Scroll chat thread to bottom on load
    document.addEventListener('DOMContentLoaded', function () {
        const thread = document.getElementById('chat-thread');
        if (thread) thread.scrollTop = thread.scrollHeight;
    });
</script>
