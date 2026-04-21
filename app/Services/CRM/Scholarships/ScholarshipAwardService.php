<?php

declare(strict_types=1);

namespace App\Services\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Enums\CRM\Scholarships\ScholarshipAwardStatus;
use App\Events\CRM\Scholarships\ScholarshipAwardApproved;
use App\Events\CRM\Scholarships\ScholarshipAwardRejected;
use App\Events\CRM\Scholarships\ScholarshipAwardSubmitted;
use App\Events\CRM\Scholarships\ScholarshipStageAdvanced;
use App\Models\CRM\Scholarships\ScholarshipApproval;
use App\Models\CRM\Scholarships\ScholarshipAward;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// BRD: CRM-FM-008 — Award approval lifecycle (counsellor -> manager -> finance).
final class ScholarshipAwardService
{
    public function submit(ScholarshipAward $award): ScholarshipAward
    {
        if ($award->status !== ScholarshipAwardStatus::DRAFT) {
            throw new DomainException('Only draft awards can be submitted.');
        }

        return DB::transaction(function () use ($award): ScholarshipAward {
            $award->status = ScholarshipAwardStatus::COUNSELLOR_SUBMITTED;
            $award->current_stage = ApprovalStage::MANAGER;
            $award->counsellor_submitted_at = now();
            $award->save();

            $this->recordApproval($award, ApprovalStage::COUNSELLOR, 'approved', null);
            event(new ScholarshipAwardSubmitted($award));

            return $award;
        });
    }

    public function approve(ScholarshipAward $award, ApprovalStage $stage, ?string $comment = null): ScholarshipAward
    {
        if ($award->current_stage !== $stage) {
            throw new DomainException("Award is not awaiting {$stage->value} approval.");
        }

        return DB::transaction(function () use ($award, $stage, $comment): ScholarshipAward {
            $this->recordApproval($award, $stage, 'approved', $comment);

            if ($stage === ApprovalStage::MANAGER) {
                $award->status = ScholarshipAwardStatus::MANAGER_APPROVED;
                $award->manager_approved_at = now();
                $award->current_stage = ApprovalStage::FINANCE;
                $award->save();
                event(new ScholarshipStageAdvanced($award));
            } elseif ($stage === ApprovalStage::FINANCE) {
                $award->status = ScholarshipAwardStatus::FINANCE_APPROVED;
                $award->finance_approved_at = now();
                $award->save();
                event(new ScholarshipAwardApproved($award));
            }

            return $award->fresh();
        });
    }

    public function reject(ScholarshipAward $award, ApprovalStage $stage, string $reason): ScholarshipAward
    {
        if ($award->current_stage !== $stage) {
            throw new DomainException("Award is not awaiting {$stage->value} decision.");
        }

        return DB::transaction(function () use ($award, $stage, $reason): ScholarshipAward {
            $this->recordApproval($award, $stage, 'rejected', $reason);
            $award->status = ScholarshipAwardStatus::REJECTED;
            $award->rejection_reason = $reason;
            $award->rejected_at = now();
            $award->save();
            event(new ScholarshipAwardRejected($award));

            return $award->fresh();
        });
    }

    public function withdraw(ScholarshipAward $award): ScholarshipAward
    {
        if ($award->status->isTerminal()) {
            throw new DomainException('Award is already terminal.');
        }
        $award->status = ScholarshipAwardStatus::WITHDRAWN;
        $award->withdrawn_at = now();
        $award->save();

        return $award;
    }

    private function recordApproval(ScholarshipAward $award, ApprovalStage $stage, string $decision, ?string $comment): void
    {
        ScholarshipApproval::create([
            'institution_id'       => $award->institution_id,
            'scholarship_award_id' => $award->id,
            'stage'                => $stage->value,
            'decision'             => $decision,
            'actor_id'             => Auth::id() ?? 0,
            'comment'              => $comment,
            'acted_at'             => now(),
        ]);
    }
}
