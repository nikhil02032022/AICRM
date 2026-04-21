<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Analytics;

use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Application\ConversionReportService;
use Illuminate\Support\Collection;
use Livewire\Component;

// BRD: CRM-AP-019 — Conversion rate Livewire component (applications → enrolled by programme/batch/source/counsellor)
class ConversionRates extends Component
{
    public array $filters = [];
    public $stats;
    public Collection $programmes;
    public Collection $counsellors;
    public Collection $batches;

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

        $this->batches = Lead::where('institution_id', auth()->user()->institution_id)
            ->whereNotNull('preferred_intake')
            ->distinct()
            ->orderBy('preferred_intake')
            ->pluck('preferred_intake');

        $this->stats = $reportService->getConversionRates($this->filters);
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'filters')) {
            $this->stats = app(ConversionReportService::class)->getConversionRates($this->filters);
        }
    }

    public function applyFilters(): void
    {
        $this->stats = app(ConversionReportService::class)->getConversionRates($this->filters);
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->stats = app(ConversionReportService::class)->getConversionRates([]);
    }

    public function render()
    {
        return view('livewire.crm.analytics.conversion-rates', [
            'stats'       => $this->stats,
            'filters'     => $this->filters,
            'programmes'  => $this->programmes,
            'counsellors' => $this->counsellors,
            'batches'     => $this->batches,
        ]);
    }
}
