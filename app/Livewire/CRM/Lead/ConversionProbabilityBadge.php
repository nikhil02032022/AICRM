<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Lead;

use App\Enums\CRM\AI\PredictionStatus;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Lead;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

// BRD: CRM-AI-001 — Reactive badge showing Claude API conversion probability, confidence level, and accept/reject actions
final class ConversionProbabilityBadge extends Component
{
    public string $leadUuid = '';

    /** @return AiLeadScore|null */
    #[Computed]
    public function latestScore(): ?AiLeadScore
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            return null;
        }

        return AiLeadScore::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->whereNotNull('prediction_status')
            ->latest('calculated_at')
            ->first();
    }

    public function shouldPoll(): bool
    {
        $score = $this->latestScore;

        if ($score === null) {
            return false;
        }

        return $score->prediction_status === PredictionStatus::Pending
            || $score->prediction_status === PredictionStatus::Processing;
    }

    public function render(): View
    {
        return view('livewire.crm.leads.conversion-probability-badge');
    }
}
