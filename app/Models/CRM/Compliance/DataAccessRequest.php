<?php

declare(strict_types=1);

namespace App\Models\CRM\Compliance;

use App\Enums\CRM\Compliance\DataAccessStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAccessRequest extends Model
{
    protected $table = 'data_access_requests';

    /** @var list<string> */
    protected $fillable = [
        'lead_id',
        'institution_id',
        'requested_at',
        'processed_at',
        'delivery_method',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status'       => DataAccessStatus::class,
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
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
}
