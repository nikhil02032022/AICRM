<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Enums\CRM\Tasks\TaskType;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// BRD: CRM-TF-001, TF-003 — Reactive task list with filtering and overdue highlighting
final class TaskList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filterStatus = '';

    #[Url(except: '')]
    public string $filterType = '';

    #[Url(except: '')]
    public string $filterPriority = '';

    #[Url(except: '')]
    public string $filterDateFrom = '';

    #[Url(except: '')]
    public string $filterDateTo = '';

    public int $perPage = 20;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function tasks(): LengthAwarePaginator
    {
        return app(TaskRepositoryInterface::class)->paginateForCounsellor(
            Auth::user(),
            [
                'search'     => $this->search,
                'status'     => $this->filterStatus,
                'type'       => $this->filterType,
                'priority'   => $this->filterPriority,
                'due_from'   => $this->filterDateFrom,
                'due_to'     => $this->filterDateTo,
                'per_page'   => $this->perPage,
            ],
        );
    }

    #[Computed]
    public function statuses(): array
    {
        return TaskStatus::cases();
    }

    #[Computed]
    public function types(): array
    {
        return TaskType::cases();
    }

    #[Computed]
    public function priorities(): array
    {
        return TaskPriority::cases();
    }

    public function render(): View
    {
        return view('livewire.crm.tasks.task-list');
    }
}
