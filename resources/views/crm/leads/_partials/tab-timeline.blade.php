                    {{-- ── Timeline tab ── --}}
                    <div x-show="tab === 'timeline'"
                         x-transition:enter="transition-opacity duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="p-0">
                        {{-- BRD: CRM-EC-004 — Reactive CRM activity timeline (Livewire) --}}
                        <livewire:crm.lead.lead-activity-timeline :lead-uuid="$lead->uuid" :key="'timeline-'.$lead->uuid" />
                    </div>{{-- end timeline tab panel --}}