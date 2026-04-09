{{-- BRD: CRM-EC-004 — Lead 360° detail view with complete activity timeline (annotation was incorrectly CRM-LC-011) --}}
<x-layouts.crm :title="$lead->fullName()">
    @php
        $primaryProg = $lead->programmeInterests->firstWhere('pivot.is_primary', true)
                    ?? $lead->programmeInterests->first();
        $circ        = round(2 * M_PI * 30, 2);
        $filled      = round(($lead->lead_score / 100) * $circ, 2);
        $scoreColour = match(true) {
            $lead->lead_score >= 75 => '#10B981',
            $lead->lead_score >= 50 => '#F59E0B',
            default                 => '#6366F1',
        };
        $daysActive  = max(1, (int) ($lead->created_at?->diffInDays(now()) ?? 1));
        $touchpoints = $auditLogs->count();
    @endphp

    <div class="space-y-5" x-data="leadDetailPage()">

        @include('crm.leads._partials.header')

        {{-- ── 2-column grid ── --}}
        <div class="flex items-start gap-5">

            @include('crm.leads._partials.sidebar')

            {{-- ── RIGHT: Stats + Tabbed content ── --}}
            <div class="min-w-0 flex-1 space-y-5">

                @include('crm.leads._partials.stats')

                @include('crm.leads._partials.tabs')

            </div>{{-- end RIGHT --}}

        </div>{{-- end 2-col grid --}}

        @include('crm.leads._partials.modals')

    </div>{{-- end x-data="leadDetailPage()" --}}

    @push('scripts')
    <script>
    function leadDetailPage() {
        return {
            // ── Edit modal ──
            editOpen: false,
            editSubmitting: false,
            editErrors: {},
            editGlobalError: '',
            editForm: {
                first_name: @json($lead->first_name),
                last_name:  @json($lead->last_name),
                email:      @json($lead->email ?? ''),
                source:     @json($lead->source?->value ?? ''),
                status:     @json($lead->status?->value ?? ''),
                city:       @json($lead->city ?? ''),
                state:      @json($lead->state ?? ''),
                notes:      @json($lead->notes ?? ''),
            },

            openEdit()  { this.editOpen = true; this.editErrors = {}; this.editGlobalError = ''; },
            closeEdit() { this.editOpen = false; this.editSubmitting = false; this.editErrors = {}; this.editGlobalError = ''; },

            async submitEdit() {
                this.editSubmitting  = true;
                this.editErrors      = {};
                this.editGlobalError = '';

                try {
                    const res = await fetch('{{ route('crm.leads.update', $lead->uuid) }}', {
                        method: 'PUT',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept':       'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(this.editForm),
                    });

                    const json = await res.json();

                    if (json.success) {
                        this.closeEdit();
                        window.location.reload();
                        return;
                    }

                    if (res.status === 422 && json.errors) {
                        this.editErrors = Object.fromEntries(
                            Object.entries(json.errors).map(([k, v]) => [k, v[0]])
                        );
                    } else {
                        this.editGlobalError = json.error?.message ?? 'An unexpected error occurred.';
                    }
                } catch {
                    this.editGlobalError = 'Network error. Please try again.';
                } finally {
                    this.editSubmitting = false;
                }
            },

            // ── Delete modal ──
            deleteOpen: false,
            deleteSubmitting: false,

            openDelete()  { this.deleteOpen = true; },
            closeDelete() { this.deleteOpen = false; this.deleteSubmitting = false; },

            async submitDelete() {
                this.deleteSubmitting = true;
                try {
                    const res = await fetch('{{ route('crm.leads.destroy', $lead->uuid) }}', {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: {
                            'Accept':       'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    const json = await res.json();
                    if (json.success) {
                        window.location.href = '{{ route('crm.leads.index') }}';
                        return;
                    }
                    this.closeDelete();
                    this.editGlobalError = json.error?.message ?? 'Could not archive lead.';
                } catch {
                    this.closeDelete();
                } finally {
                    this.deleteSubmitting = false;
                }
            },
        };
    }
    </script>
    @endpush

</x-layouts.crm>


