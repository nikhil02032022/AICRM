<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Counselling;

use App\Services\CRM\Counselling\CounsellorAssignmentService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

// BRD: CRM-EC-008 — Live workload display per counsellor with refresh
final class CounsellorWorkloadDashboard extends Component
{
    public function mount(): void {}

    /** @return Collection<int, object> */
    #[Computed]
    public function counsellors(): Collection
    {
        /** @var CounsellorAssignmentService $svc */
        $svc = app(CounsellorAssignmentService::class);

        return $svc->getAvailableCounsellors(
            auth()->user()->institution_id,
        );
    }

    public function render(): View
    {
        return view('livewire.crm.counselling.counsellor-workload-dashboard');
    }
}
