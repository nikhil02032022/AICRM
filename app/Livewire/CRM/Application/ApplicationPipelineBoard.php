<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Application;

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Services\CRM\Application\ApplicationPipelineService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        $institutionId = auth()->user()->institution_id;
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
                auth()->id(),
                'Moved via Kanban board'
            );

            $this->dispatch('success', message: 'Application status updated successfully');
            unset($this->applicationsByStatus);
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
        ]);
    }
}
