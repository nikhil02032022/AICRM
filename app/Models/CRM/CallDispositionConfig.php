<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TC-003 — Institution-scoped call disposition configuration
#[ObservedBy(AuditObserver::class)]
class CallDispositionConfig extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'call_disposition_configs';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'code',
        'label',
        'is_active',
        'requires_follow_up',
        'sort_order',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_follow_up' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
