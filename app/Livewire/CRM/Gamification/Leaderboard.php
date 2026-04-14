<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Gamification;

use App\Enums\CRM\PeriodType;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * BRD: CRM-EC-010 — Leaderboard Livewire component
 */
class Leaderboard extends Component
{
    public Collection $leaderboard;
    public PeriodType $periodType;
    public int $currentUserId;

    public function mount(Collection $leaderboard, PeriodType $periodType, int $currentUserId): void
    {
        $this->leaderboard = $leaderboard;
        $this->periodType = $periodType;
        $this->currentUserId = $currentUserId;
    }

    public function render()
    {
        return view('livewire.crm.gamification.leaderboard');
    }
}
