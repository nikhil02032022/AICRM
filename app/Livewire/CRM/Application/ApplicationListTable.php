<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Application;

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// BRD: CRM-AP-008, CRM-AP-009, CRM-AP-010 — Live-filtered and sortable application list table
final class ApplicationListTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filterStatus = '';

    #[Url(except: '')]
    public string $filterCounsellor = '';

    #[Url(except: '')]
    public string $filterAdmissionCycle = '';

    #[Url(except: '')]
    public string $filterDateFrom = '';

    #[Url(except: '')]
    public string $filterDateTo = '';

    public string $sortField = 'submitted_at';

    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public array $selectedApplications = [];

    public bool $selectAll = false;

    public ?string $bulkAction = null;

    /** Reset pagination when filters change */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCounsellor(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAdmissionCycle(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedApplications = $this->applications->pluck('uuid')->toArray();
        } else {
            $this->selectedApplications = [];
        }
    }

    public function toggleApplicationSelection(string $applicationUuid): void
    {
        if (in_array($applicationUuid, $this->selectedApplications)) {
            $this->selectedApplications = array_filter(
                $this->selectedApplications,
                fn ($id) => $id !== $applicationUuid
            );
        } else {
            $this->selectedApplications[] = $applicationUuid;
        }
        $this->selectAll = false;
    }

    /**
     * Execute bulk action on selected applications.
     * BRD: CRM-AP-010 — Bulk status update, assignment, communication, export
     */
    public function executeBulkAction(?string $action = null): void
    {
        if (empty($this->selectedApplications)) {
            $this->dispatch('error', message: 'No applications selected');
            return;
        }

        $actionToRun = $action ?? $this->bulkAction;

        match ($actionToRun) {
            'bulk-status-update' => $this->dispatchBrowserEvent('show-bulk-status-modal'),
            'bulk-assign-counsellor' => $this->dispatchBrowserEvent('show-bulk-assign-modal'),
            'bulk-export' => $this->export(),
            default => $this->dispatch('error', message: 'Unknown bulk action'),
        };
    }

    private function export(): void
    {
        // TODO: Dispatch export job
        $this->dispatch('success', message: 'Export job started. You will receive a download link via email.');
        $this->selectedApplications = [];
        $this->selectAll = false;
    }

    #[Computed]
    public function applications(): LengthAwarePaginator
    {
        $institutionId = auth()->user()->institution_id;

        $query = Application::where('institution_id', $institutionId);

        if ($this->search) {
            $query->whereHas('lead', function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCounsellor) {
            $query->where('assigned_counsellor_id', $this->filterCounsellor);
        }

        if ($this->filterAdmissionCycle) {
            $query->where('admission_cycle_uuid', $this->filterAdmissionCycle);
        }

        if ($this->filterDateFrom) {
            $query->whereDate('submitted_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('submitted_at', '<=', $this->filterDateTo);
        }

        return $query->with(['lead', 'assignedCounsellor', 'currentOfferLetter'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.crm.application.list-table', [
            'applications' => $this->applications,
            'statuses' => ApplicationStatus::cases(),
        ]);
    }
}
