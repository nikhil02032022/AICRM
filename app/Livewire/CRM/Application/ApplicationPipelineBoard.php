<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Application;

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Services\CRM\Application\ApplicationPipelineService;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AP-008, CRM-AP-009 — Kanban board for application pipeline stages
final class ApplicationPipelineBoard extends Component
{
    private readonly ApplicationPipelineService $pipelineService;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filterCounsellor = '';

    #[Url(except: '')]
    public string $filterAdmissionCycle = '';

    public function __construct()
    {
        $this->pipelineService = app(ApplicationPipelineService::class);
    }

    /**
     * Get applications grouped by status for Kanban columns.
     * Each column displays applications in a specific stage.
     */
    #[Computed]
    public function applicationsByStatus(): array
    {
        $institutionId = (int) Auth::user()?->institution_id;
        $columns = [];

        foreach (ApplicationStatus::cases() as $status) {
            $query = Application::where('institution_id', $institutionId)
                ->where('status', $status);

            if ($this->search) {
                $query->whereHas('lead', function ($q) {
                    $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            }

            if ($this->filterCounsellor) {
                $query->where('assigned_counsellor_id', $this->filterCounsellor);
            }

            if ($this->filterAdmissionCycle) {
                $query->where('admission_cycle_uuid', $this->filterAdmissionCycle);
            }

            $columns[$status->value] = [
                'status' => $status,
                'label' => $status->label(),
                'badgeColour' => $status->badgeColour(),
                'applications' => $query->with(['lead', 'assignedCounsellor', 'currentOfferLetter'])
                    ->orderByDesc('stage_entered_at')
                    ->get(),
                'count' => $query->count(),
            ];
        }

        return $columns;
    }

    /**
     * BRD: CRM-AP-011 — Programme-wise seat availability vs application count.
     *
     * @return array<int, array<string, int|float|string|null>>
     */
    #[Computed]
    public function seatAvailabilityOverview(): array
    {
        return $this->pipelineService->seatAvailabilityOverview((int) Auth::user()?->institution_id);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function seatCapacityTotals(): array
    {
        $overview = collect($this->seatAvailabilityOverview);

        return [
            'programme_count' => $overview->count(),
            'total_seats' => (int) $overview->sum('total_seats'),
            'application_count' => (int) $overview->sum('application_count'),
            'available_seats' => (int) $overview->sum('available_seats'),
            'critical_programmes' => (int) $overview
                ->whereIn('capacity_status', ['critical', 'full'])
                ->count(),
        ];
    }

    #[Computed]
    public function counsellors(): Collection
    {
        return User::query()
            ->where('institution_id', (int) Auth::user()?->institution_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Handle drag-and-drop transition to new column.
     * BRD: CRM-AP-009 — Validate state transition before updating
     */
    public function transitionApplication(string $applicationUuid, string $toStatus): void
    {
        Gate::authorize('transition', Application::whereUuid($applicationUuid)->firstOrFail());

        try {
            $application = Application::whereUuid($applicationUuid)->firstOrFail();
            $newStatus = ApplicationStatus::from($toStatus);

            $this->pipelineService->transition(
                $application,
                $newStatus,
                Auth::id(),
                'Moved via Kanban board'
            );

            $this->dispatch('success', message: 'Application status updated successfully');
            unset($this->applicationsByStatus);
            unset($this->seatAvailabilityOverview);
            unset($this->seatCapacityTotals);
        } catch (\ValueError $e) {
            $this->dispatch('error', message: 'Invalid status');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function updatedSearch(): void
    {
        unset($this->applicationsByStatus);
    }

    public function updatedFilterCounsellor(): void
    {
        unset($this->applicationsByStatus);
    }

    public function updatedFilterAdmissionCycle(): void
    {
        unset($this->applicationsByStatus);
    }

    public function render(): View
    {
        return view('livewire.crm.application.pipeline-board', [
            'columnsByStatus' => $this->applicationsByStatus,
            'seatAvailabilityOverview' => $this->seatAvailabilityOverview,
            'seatCapacityTotals' => $this->seatCapacityTotals,
            'counsellors' => $this->counsellors,
        ]);
    }
}
