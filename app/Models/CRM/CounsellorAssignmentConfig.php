<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\AssignmentMode;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-EC-006 — Per-institution counsellor assignment configuration
final class CounsellorAssignmentConfig extends Model
{
    use HasUuids;

    protected $table = 'counsellor_assignment_configs';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        self::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'assignment_mode',
        'max_leads_per_counsellor',
        'round_robin_pointer_user_id',
        'escalation_hours',
        'escalation_to_user_id',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'assignment_mode' => AssignmentMode::class,
            'max_leads_per_counsellor' => 'integer',
            'escalation_hours' => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function roundRobinPointer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'round_robin_pointer_user_id');
    }

    public function escalationTarget(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalation_to_user_id');
    }
}
