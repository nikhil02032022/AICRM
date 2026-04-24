<?php

declare(strict_types=1);

namespace App\Models\CRM\Compliance;

use App\Enums\CRM\Compliance\OptOutChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptOutLog extends Model
{
    protected $table = 'opt_out_logs';

    /** @var list<string> */
    protected $fillable = [
        'lead_id',
        'institution_id',
        'channel',
        'requested_at',
        'processed_at',
        'processed_by_job',
    ];

    protected function casts(): array
    {
        return [
            'channel'          => OptOutChannel::class,
            'requested_at'     => 'datetime',
            'processed_at'     => 'datetime',
            'processed_by_job' => 'boolean',
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

    public function isPending(): bool
    {
        return $this->processed_at === null;
    }
}
