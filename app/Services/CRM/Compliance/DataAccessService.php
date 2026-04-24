<?php

declare(strict_types=1);

namespace App\Services\CRM\Compliance;

use App\Enums\CRM\Compliance\DataAccessStatus;
use App\Models\CRM\Compliance\DataAccessRequest;
use App\Models\CRM\Lead;
use Illuminate\Support\Facades\Mail;

// BRD: CRM-CR-004 — Right-to-access: applicant can request a copy of stored data
class DataAccessService
{
    public function request(Lead $lead, string $deliveryMethod, int $institutionId): DataAccessRequest
    {
        return DataAccessRequest::create([
            'lead_id'         => $lead->id,
            'institution_id'  => $institutionId,
            'requested_at'    => now(),
            'delivery_method' => $deliveryMethod,
            'status'          => DataAccessStatus::Pending->value,
        ]);
    }

    public function compile(DataAccessRequest $request): array
    {
        $lead = Lead::withoutGlobalScopes()
            ->with(['applications', 'consentRecords', 'auditLogs'])
            ->findOrFail($request->lead_id);

        return [
            'personal' => [
                'first_name' => $lead->first_name,
                'last_name'  => $lead->last_name,
                'mobile'     => $lead->mobile,
                'email'      => $lead->email,
                'source'     => $lead->source?->label(),
                'status'     => $lead->status?->label(),
            ],
            'applications'   => $lead->applications->map(fn ($a) => [
                'uuid'    => $a->uuid,
                'status'  => $a->status?->label(),
                'created' => $a->created_at?->toDateString(),
            ])->all(),
            'consent_records' => $lead->consentRecords->map(fn ($c) => [
                'type'         => $c->consent_type,
                'consented_at' => $c->consented_at?->toDateTimeString(),
                'ip'           => $c->ip_address,
            ])->all(),
        ];
    }

    public function deliver(DataAccessRequest $request): void
    {
        $request->update([
            'status'       => DataAccessStatus::Processing->value,
            'processed_at' => now(),
        ]);

        // In production: send compiled data via email to lead's email address
        // Stub: mark as delivered
        $request->update(['status' => DataAccessStatus::Delivered->value]);
    }
}
