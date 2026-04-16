<?php

declare(strict_types=1);

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AP-009 — Application status transition audit trail
class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';

    public $timestamps = true;
    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'application_uuid',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'reason',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_uuid', 'uuid');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by_user_id');
    }
}
