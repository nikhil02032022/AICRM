<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Counselling;

use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\WalkInToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

// BRD: CRM-EC-019 — Public TV display screen; Echo-driven, no auth, token numbers only (no PII)
final class QueueDisplay extends Component
{
    public int $campusId;

    public function mount(Institution $institution): void
    {
        $campus = $institution->campuses()->where('is_active', true)->first();
        $this->campusId = $campus?->id ?? 0;
    }

    /** @return WalkInToken|null */
    #[Computed]
    public function currentToken(): ?WalkInToken
    {
        return WalkInToken::withoutGlobalScopes()
            ->where('campus_id', $this->campusId)
            ->whereDate('token_date', Carbon::today())
            ->where('status', WalkInTokenStatus::CALLED->value)
            ->orderByDesc('called_at')
            ->first();
    }

    /** @return Collection<int, WalkInToken> */
    #[Computed]
    public function recentTokens(): Collection
    {
        return WalkInToken::withoutGlobalScopes()
            ->where('campus_id', $this->campusId)
            ->whereDate('token_date', Carbon::today())
            ->whereIn('status', [
                WalkInTokenStatus::CALLED->value,
                WalkInTokenStatus::SERVING->value,
                WalkInTokenStatus::SERVED->value,
            ])
            ->orderByDesc('called_at')
            ->limit(5)
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

    public function render(): View
    {
        return view('livewire.crm.counselling.queue-display');
    }
}
