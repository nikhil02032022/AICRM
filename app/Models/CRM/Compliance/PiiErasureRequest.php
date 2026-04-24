<?php

declare(strict_types=1);

namespace App\Models\CRM\Compliance;

use App\Enums\CRM\Compliance\PiiErasureStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiiErasureRequest extends Model
{
    protected $table = 'pii_erasure_requests';

    /** @var list<string> */
    protected $fillable = [
        'lead_id',
        'institution_id',
        'requested_at',
        'scheduled_erasure_at',
        'erased_at',
        'erased_by_job',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status'               => PiiErasureStatus::class,
            'requested_at'         => 'datetime',
            'scheduled_erasure_at' => 'datetime',
            'erased_at'            => 'datetime',
            'erased_by_job'        => 'boolean',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Lead::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    public function isDue(): bool
    {
        return $this->status === PiiErasureStatus::Scheduled
            && $this->scheduled_erasure_at !== null
            && $this->scheduled_erasure_at->isPast();
    }
}
