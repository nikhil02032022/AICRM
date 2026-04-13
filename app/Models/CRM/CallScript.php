<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CallScriptStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TC-002 — Call script definition with branching steps
#[ObservedBy(AuditObserver::class)]
class CallScript extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'call_scripts';

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
        'name',
        'status',
        'description',
        'is_default',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CallScriptStatus::class,
            'is_default' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CallScriptStep::class, 'call_script_id')->orderBy('step_order');
    }
}
