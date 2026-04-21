<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Tasks\Manager;

use App\Enums\CRM\ActivityType;
use App\Models\CRM\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// BRD: CRM-TF-007 — Real-time manager activity feed for task events
final class ActivityFeed extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $filterCounsellor = '';

    #[Url(except: '')]
    public string $filterDateFrom = '';

    #[Url(except: '')]
    public string $filterDateTo = '';

    public int $perPage = 25;

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        $institutionId = Auth::user()->institution_id;

        $taskTypes = [
            ActivityType::TASK_CREATED->value,
            ActivityType::TASK_COMPLETED->value,
            ActivityType::TASK_UPDATED->value,
        ];

        $query = Activity::query()
            ->where('institution_id', $institutionId)
            ->whereIn('type', $taskTypes)
            ->with(['subject', 'performedBy:id,name'])
            ->orderByDesc('created_at');

        if ($this->filterCounsellor !== '') {
            $query->where('performed_by_id', (int) $this->filterCounsellor);
        }

        if ($this->filterDateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo !== '') {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        return $query->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.crm.tasks.manager.activity-feed');
    }
}
