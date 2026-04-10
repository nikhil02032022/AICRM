<div class="p-6">

    {{-- ── Add Note form ── --}}
    @can('crm.leads.edit')
    <form wire:submit="addNote" class="mb-6">
        <label for="activity-note" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
            Add Note
        </label>
        <div class="flex gap-2">
            <textarea
                id="activity-note"
                wire:model="noteBody"
                rows="2"
                maxlength="2000"
                placeholder="Type a note, observation, or follow-up detail…"
                class="input-field flex-1 resize-none"
                aria-label="Add a note to this lead's activity timeline"
            ></textarea>
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="btn-primary self-end px-4 py-2 disabled:opacity-50"
            >
                <span wire:loading.remove>Add</span>
                <span wire:loading class="flex items-center gap-1">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                    </svg>
                    …
                </span>
            </button>
        </div>
        @error('noteBody')
            <p role="alert" class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </form>
    @endcan

    {{-- ── Timeline entries ── --}}
    @if($this->activities->isNotEmpty())
    <ul style="list-style:none;padding:0;margin:0" aria-label="Activity timeline">
        @foreach($this->activities as $activity)
        @php
            /** @var \App\Models\CRM\Activity $activity */
            $dotColour = $activity->type->dotColour();
        @endphp
        <li style="display:flex;gap:16px;padding-bottom:{{ $loop->last ? '0' : '28px' }}">
            {{-- Connector line + dot --}}
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0">
                <div style="width:10px;height:10px;border-radius:50%;background:{{ $dotColour }};flex-shrink:0;margin-top:3px;box-shadow:0 0 0 3px {{ $dotColour }}22"></div>
                @if(! $loop->last)
                <div style="width:1px;flex:1;background:#E5E7EB;margin-top:5px"></div>
                @endif
            </div>

            {{-- Content --}}
            <div class="min-w-0 flex-1 pb-1">
                {{-- Type badge --}}
                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider
                    @php
                        $badge = match($activity->type->badgeColour()) {
                            'indigo'  => 'bg-indigo-100 text-indigo-700',
                            'violet'  => 'bg-violet-100 text-violet-700',
                            'blue'    => 'bg-blue-100 text-blue-700',
                            'green'   => 'bg-green-100 text-green-700',
                            'sky'     => 'bg-sky-100 text-sky-700',
                            'emerald' => 'bg-emerald-100 text-emerald-700',
                            'teal'    => 'bg-teal-100 text-teal-700',
                            'amber'   => 'bg-amber-100 text-amber-700',
                            'lime'    => 'bg-lime-100 text-lime-700',
                            default   => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    {{ $badge }}"
                >
                    {{ $activity->type->label() }}
                </span>

                {{-- Body --}}
                @if($activity->body)
                <p class="mt-1 text-sm leading-relaxed text-gray-800 whitespace-pre-line">{{ $activity->body }}</p>
                @endif

                {{-- Metadata (non-PII structured data) --}}
                @if(! empty($activity->metadata) && $activity->type === \App\Enums\CRM\ActivityType::STATUS_CHANGE)
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ \App\Enums\CRM\LeadStatus::from($activity->metadata['from'])->label() }}
                    &rarr;
                    {{ \App\Enums\CRM\LeadStatus::from($activity->metadata['to'])->label() }}
                </p>
                @endif

                {{-- Actor + Time --}}
                <p class="mt-1 text-[11px] text-gray-400">
                    {{ $activity->created_at?->format('d M Y, h:i A') }}
                    @if($activity->performedBy)
                        &middot; {{ $activity->performedBy->name }}
                    @endif
                    @if($activity->channel)
                        &middot; via {{ ucfirst($activity->channel) }}
                    @endif
                </p>
            </div>
        </li>
        @endforeach
    </ul>

    {{-- Pagination --}}
    @if($this->activities->hasPages())
    <div class="mt-6 border-t border-gray-100 pt-4">
        {{ $this->activities->links() }}
    </div>
    @endif

    @else
    <div class="py-12 text-center">
        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="mt-3 text-sm font-medium text-gray-400">No activity recorded yet.</p>
        <p class="mt-1 text-xs text-gray-400">Status changes, calls, notes, and messages will appear here.</p>
    </div>
    @endif

</div>
