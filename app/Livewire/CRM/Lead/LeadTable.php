<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Lead;

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Lead;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// BRD: CRM-LC-011 — Live-filtered and sortable lead list table
final class LeadTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filterStatus = '';

    #[Url(except: '')]
    public string $filterTemperature = '';

    #[Url(except: '')]
    public string $filterSource = '';

    public string $sortField     = 'created_at';
    public string $sortDirection = 'desc';

    public int $perPage = 10;

    /**
     * Refresh table after a lead is created via the modal.
     * Resets all active filters so the new lead is always visible at the top.
     */
    #[On('lead-created')]
    public function onLeadCreated(): void
    {
        $this->search             = '';
        $this->filterStatus       = '';
        $this->filterTemperature  = '';
        $this->filterSource       = '';
        $this->sortField          = 'created_at';
        $this->sortDirection      = 'desc';
        $this->resetPage();
        unset($this->leads);
    }

    /** Reset pagination when filters change */
    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterTemperature(): void { $this->resetPage(); }
    public function updatedFilterSource(): void { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    #[Computed]
    public function leads(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Lead::select([
            'id', 'uuid', 'first_name', 'last_name', 'email',
            'lead_score', 'temperature', 'status', 'source',
            'assigned_counsellor_id', 'created_at', 'updated_at',
            'is_duplicate_suspected',
        ])->with([
            'assignedCounsellor:id,name',
            'programmeInterests:id,name',
        ]);

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term): void {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term);
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterTemperature !== '') {
            $query->where('temperature', $this->filterTemperature);
        }

        if ($this->filterSource !== '') {
            $query->where('source', $this->filterSource);
        }

        $allowed = ['created_at', 'lead_score', 'status'];
        $field   = in_array($this->sortField, $allowed, true) ? $this->sortField : 'created_at';
        $dir     = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($field, $dir)->paginate($this->perPage);
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        $options = ['' => 'All Statuses'];
        foreach (LeadStatus::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /** @return array<string, string> */
    public function temperatureOptions(): array
    {
        $options = ['' => 'All Temperatures'];
        foreach (LeadTemperature::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /** @return array<string, string> */
    public function sourceOptions(): array
    {
        return ['' => 'All Sources'] + LeadSource::optionsForSelect();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.crm.lead.lead-table');
    }
}
