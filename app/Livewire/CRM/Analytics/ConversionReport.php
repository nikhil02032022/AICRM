<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Analytics;

use App\Models\CRM\CrmProgramme;
use App\Models\User;
use App\Services\CRM\Application\ConversionReportService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ConversionReport extends Component
{
    public array $filters = [];
    public $stats;
    public Collection $programmes;
    public Collection $counsellors;

    protected $queryString = [
        'filters',
    ];

    public function mount(ConversionReportService $reportService): void
    {
        $this->programmes = CrmProgramme::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->counsellors = User::where('institution_id', auth()->user()->institution_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->stats = $reportService->getGroupedStats($this->filters);
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'filters')) {
            $this->stats = app(ConversionReportService::class)->getGroupedStats($this->filters);
        }
    }

    public function applyFilters(): void
    {
        $this->stats = app(ConversionReportService::class)->getGroupedStats($this->filters);
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->stats = app(ConversionReportService::class)->getGroupedStats([]);
    }

    public function render()
    {
        return view('livewire.crm.analytics.conversion-report', [
            'stats' => $this->stats,
            'filters' => $this->filters,
            'programmes' => $this->programmes,
            'counsellors' => $this->counsellors,
        ]);
    }
}
