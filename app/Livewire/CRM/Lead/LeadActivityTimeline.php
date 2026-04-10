<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Lead;

use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Models\CRM\Activity;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

// BRD: CRM-EC-004 — Reactive activity timeline displayed on the lead show page
final class LeadActivityTimeline extends Component
{
    use WithPagination;

    public string $leadUuid = '';

    public string $noteBody = '';

    protected int $leadId = 0;

    protected int $institutionId = 0;

    public function mount(string $leadUuid): void
    {
        $this->leadUuid = $leadUuid;

        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $leadUuid)
            ->select(['id', 'institution_id'])
            ->firstOrFail();

        $this->leadId = $lead->id;
        $this->institutionId = $lead->institution_id;
    }

    /** Refresh after external activity events (e.g. assignment, scoring). */
    #[On('activity-added')]
    public function onActivityAdded(): void
    {
        unset($this->activities);
        $this->resetPage();
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        // Hydrate lead IDs from the uuid on each compute (after page refresh)
        if ($this->leadId === 0) {
            $lead = Lead::withoutGlobalScopes()
                ->where('uuid', $this->leadUuid)
                ->select(['id', 'institution_id'])
                ->firstOrFail();
            $this->leadId = $lead->id;
            $this->institutionId = $lead->institution_id;
        }

        return Activity::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->where('subject_type', Lead::class)
            ->where('subject_id', $this->leadId)
            ->with('performedBy:id,name')
            ->latest('created_at')
            ->paginate(20);
    }

    /**
     * Add a counsellor note to the activity timeline.
     *
     * BRD: CRM-EC-003 — Counsellor notes with timestamp
     * DPDP: body is user-controlled text — not scrubbed here, but never auto-populated with PII
     */
    public function addNote(): void
    {
        $this->validate([
            'noteBody' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $user = Auth::user();

        if ($this->leadId === 0) {
            $lead = Lead::withoutGlobalScopes()
                ->where('uuid', $this->leadUuid)
                ->select(['id', 'institution_id'])
                ->firstOrFail();
            $this->leadId = $lead->id;
            $this->institutionId = $lead->institution_id;
        }

        /** @var ActivityRepositoryInterface $repo */
        $repo = app(ActivityRepositoryInterface::class);

        $repo->createForSubject(new CreateActivityDTO(
            type: ActivityType::NOTE,
            subjectType: Lead::class,
            subjectId: $this->leadId,
            institutionId: $this->institutionId,
            body: $this->noteBody,
            channel: null,
            direction: 'internal',
            metadata: null,
            performedById: $user?->id,
        ));

        $this->noteBody = '';
        unset($this->activities);
        $this->resetPage();

        $this->dispatch('activity-added');
    }

    public function render(): View
    {
        return view('livewire.crm.lead.lead-activity-timeline');
    }
}
