<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\ReportSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReportSchedule
 *
 * BRD: CRM-AR-020 — API resource for scheduled report delivery config
 */
class ReportScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'name'             => $this->name,
            'frequency'        => $this->frequency->value,
            'frequency_label'  => $this->frequency->label(),
            'day_of_week'      => $this->day_of_week,
            'day_of_month'     => $this->day_of_month,
            'run_time'         => $this->run_time,
            'recipient_emails' => $this->recipient_emails,
            'format'           => $this->format->value,
            'format_label'     => $this->format->label(),
            'is_active'        => $this->is_active,
            'last_sent_at'     => $this->last_sent_at?->toIso8601String(),
            'next_run_at'      => $this->next_run_at?->toIso8601String(),
            'custom_report'    => $this->whenLoaded('customReport', fn () => [
                'uuid' => $this->customReport->uuid,
                'name' => $this->customReport->name,
            ]),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
