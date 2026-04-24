<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Counselling;

use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Models\CRM\Campus;
use App\Models\CRM\WalkInToken;
use App\Services\CRM\Counselling\WalkInQueueService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

// BRD: CRM-EC-019 — Counsellor real-time queue panel with Echo subscription
final class WalkInQueue extends Component
{
    public int $campusId;

    public function mount(Campus $campus): void
    {
        $this->campusId = $campus->id;
    }

    /** @return Collection<int, WalkInToken> */
    #[Computed]
    public function tokens(): Collection
    {
        return WalkInToken::withoutGlobalScopes()
            ->where('campus_id', $this->campusId)
            ->whereDate('token_date', Carbon::today())
            ->whereNotIn('status', [
                WalkInTokenStatus::SERVED->value,
                WalkInTokenStatus::SKIPPED->value,
            ])
            ->orderBy('token_number')
            ->get();
    }

    /** @return array<string, string> */
    public function getListeners(): array
    {
        return [
            "echo:walk-in.{$this->campusId},token.called" => '$refresh',
            "echo:walk-in.{$this->campusId},token.status_changed" => '$refresh',
        ];
    }

    public function callNext(): void
    {
        $this->dispatch('callNextRequested');
    }

    public function render(): View
    {
        return view('livewire.crm.counselling.walk-in-queue');
    }
}
