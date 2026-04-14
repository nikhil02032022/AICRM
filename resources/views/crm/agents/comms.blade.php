{{-- BRD: AG-008 — Agent Bulk Communications: send Email/WhatsApp/SMS to agent channel partners --}}
<x-layouts.crm title="Agent Communications">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Agent Communications</h1>
                <p class="mt-1 text-sm text-gray-500">Send bulk emails, WhatsApp, or SMS updates to channel partners. Opt-out preferences are always respected.</p>
            </div>
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'compose-agent-comms')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Compose Message
            </button>
        </div>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Channel filter tabs --}}
    <div class="mb-4 flex gap-2">
        @foreach(['all' => 'All Channels', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS'] as $val => $label)
            <a href="{{ route('crm.agents.comms.index', array_merge(request()->query(), ['channel' => $val])) }}"
               class="rounded-full border px-3 py-1 text-xs font-medium transition
                   {{ request('channel', 'all') === $val ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Communications Log Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Channel</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Subject / Message</th>
                    <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">Recipients</th>
                    <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">Delivered</th>
                    <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">Failed</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Sent By</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Sent</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            @php
                                $channelColors = ['email' => 'blue', 'whatsapp' => 'green', 'sms' => 'amber'];
                                $cc = $channelColors[$log->channel->value] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $cc }}-100 text-{{ $cc }}-800">
                                {{ Str::upper($log->channel->value) }}
                            </span>
                        </td>
                        <td class="max-w-xs px-4 py-3">
                            @if($log->subject)
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $log->subject }}</p>
                            @endif
                            <p class="text-xs text-gray-500 truncate">{{ Str::limit($log->message_body, 80) }}</p>
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-semibold text-gray-700">{{ $log->recipient_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-green-700">{{ $log->delivered_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold {{ $log->failed_count > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                {{ $log->failed_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $log->sender?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                            No agent communication logs yet. Click <span class="font-semibold">Compose Message</span> to send the first one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Compose Modal --}}
    <div
        x-data="{ open: false, channel: '{{ old('channel', 'email') }}' }"
        x-on:open-modal.window="if ($event.detail === 'compose-agent-comms') open = true"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        style="display:none"
        x-cloak
    >
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Compose Agent Message</h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('crm.agents.comms.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="channel" value="email" x-model="channel"> Email</label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="channel" value="whatsapp" x-model="channel"> WhatsApp</label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="channel" value="sms" x-model="channel"> SMS</label>
                    </div>
                    @error('channel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Subject (email only) --}}
                <div x-show="channel === 'email'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" placeholder="Email subject"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        value="{{ old('subject') }}">
                    @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="message_body" required rows="4" placeholder="Enter message content..."
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">{{ old('message_body') }}</textarea>
                    @error('message_body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Agent User IDs</label>
                    <input type="text" name="recipient_agent_ids_raw" id="recipient_agent_ids_raw"
                        placeholder="Comma-separated user IDs: 5, 12, 34"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        value="{{ old('recipient_agent_ids_raw') }}">
                    <p class="mt-1 text-xs text-gray-500">Enter agent user IDs separated by commas. Opt-out preferences are automatically respected.</p>
                    {{-- Hidden actual input --}}
                    <script>
                        document.getElementById('recipient_agent_ids_raw').addEventListener('change', function() {
                            const ids = this.value.split(',').map(s => s.trim()).filter(Boolean);
                            const form = this.closest('form');
                            form.querySelectorAll('[name="recipient_agent_ids[]"]').forEach(el => el.remove());
                            ids.forEach(id => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'recipient_agent_ids[]';
                                input.value = id;
                                form.appendChild(input);
                            });
                        });
                    </script>
                    @error('recipient_agent_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-md border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    DPDP: Messages will only be sent to agents who have not opted out. Opt-out takes effect within 24 hours.
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.crm>
