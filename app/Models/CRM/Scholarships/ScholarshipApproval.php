<?php

declare(strict_types=1);

namespace App\Models\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-FM-008 — Per-stage audit row
class ScholarshipApproval extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'scholarship_approvals';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'scholarship_award_id',
        'stage', 'decision', 'actor_id', 'comment', 'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => ApprovalStage::class,
            'acted_at' => 'datetime',
        ];
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(ScholarshipAward::class, 'scholarship_award_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
