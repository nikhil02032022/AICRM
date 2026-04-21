<?php

declare(strict_types=1);

namespace App\Listeners\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Events\CRM\Scholarships\ScholarshipAwardSubmitted;
use App\Events\CRM\Scholarships\ScholarshipStageAdvanced;
use App\Models\User;
use App\Notifications\CRM\Scholarships\ApprovalPendingNotification;
use Spatie\Permission\Models\Role;

// BRD: CRM-FM-008 — Route approval notifications to the configured role for each stage.
class NotifyNextApproverOnStageAdvance
{
    public function handle(ScholarshipAwardSubmitted|ScholarshipStageAdvanced $event): void
    {
        $award = $event->award;
        $stage = $award->current_stage instanceof ApprovalStage ? $award->current_stage->value : (string) $award->current_stage;
        $roleKey = (string) config("crm_scholarships.approval_chain.{$stage}");

        if ($roleKey === '') {
            return;
        }

        $role = Role::where('name', $roleKey)->first();
        if (! $role) {
            return;
        }

        $users = User::role($role->name)
            ->where('institution_id', $award->institution_id)
            ->get();

        foreach ($users as $user) {
            $user->notify(new ApprovalPendingNotification($award));
        }
    }
}
