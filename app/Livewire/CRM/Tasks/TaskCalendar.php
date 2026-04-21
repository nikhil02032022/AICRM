<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Tasks;

use App\DTOs\CRM\Tasks\TaskCalendarQueryDTO;
use App\Services\CRM\Tasks\TaskCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

// BRD: CRM-TF-009 — FullCalendar-powered task calendar (Day/Week/Month views)
final class TaskCalendar extends Component
{
    #[Url(except: 'week')]
    public string $viewType = 'week';

    public string $currentDate = '';

    public function mount(): void
    {
        $this->currentDate = now()->toDateString();
    }

    public function getEvents(string $start, string $end): array
    {
        $dto = new TaskCalendarQueryDTO(
            start: Carbon::parse($start),
            end: Carbon::parse($end),
            viewType: $this->viewType,
        );

        return app(TaskCalendarService::class)->buildCalendarEvents(Auth::user(), $dto);
    }

    public function setView(string $viewType): void
    {
        if (in_array($viewType, ['day', 'week', 'month'], true)) {
            $this->viewType = $viewType;
            $this->dispatch('calendar-view-changed', viewType: $viewType);
        }
    }

    public function render(): View
    {
        return view('livewire.crm.tasks.task-calendar');
    }
}
