        {{-- ── Page header ── --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('crm.leads.index') }}"
                   class="btn-secondary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $lead->fullName() }}</h1>
                    <p class="mt-0.5 text-xs text-gray-500">
                       
                        &middot; Lead since {{ $lead->created_at?->format('d M Y') }}
                        @if($lead->institution)&middot; {{ $lead->institution->name }}@endif
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @can('crm.leads.edit', $lead)
                <button type="button" @click="openEdit()" class="btn-secondary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </button>
                @endcan
                @can('crm.leads.delete', $lead)
                <button type="button" @click="openDelete()" class="btn-secondary-sm border-red-200 text-red-600 hover:bg-red-50 hover:border-red-400">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
                @endcan
                <button type="button" class="btn-secondary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Application
                </button>
                @if($lead->canConvertToStudent())
                <button type="button" class="btn-primary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Convert to Student
                </button>
                @endif
            </div>
        </div>

        {{-- BRD: CRM-LC-018 — Duplicate suspected banner ── --}}
        @if($lead->is_duplicate_suspected)
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5"
             role="alert" aria-live="polite">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-900">Possible duplicate lead detected</p>
                <p class="mt-0.5 text-xs text-amber-700">
                    This lead matches an existing record on mobile, email, or name + programme.
                    @if($lead->duplicate_of_uuid)
                        <a href="{{ route('crm.leads.show', $lead->duplicate_of_uuid) }}"
                           class="font-medium underline underline-offset-2 hover:text-amber-900 transition-colors">
                            View suspected original lead →
                        </a>
                    @endif
                </p>
            </div>
        </div>
        @endif

