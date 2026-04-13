<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CallScriptResponseType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TC-002 — Step in a call script with branch conditions and next-step routing
#[ObservedBy(AuditObserver::class)]
class CallScriptStep extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'call_script_steps';

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
        'call_script_id',
        'step_key',
        'step_order',
        'prompt_text',
        'response_type',
        'options',
        'branch_rules',
        'default_next_step_key',
        'is_terminal',
    ];

    protected function casts(): array
    {
        return [
            'response_type' => CallScriptResponseType::class,
            'options' => 'array',
            'branch_rules' => 'array',
            'is_terminal' => 'boolean',
        ];
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(CallScript::class, 'call_script_id');
    }
}
