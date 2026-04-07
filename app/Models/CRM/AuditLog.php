<?php

declare(strict_types=1);

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * AuditLog — Read-only model for querying the audit_logs table.
 *
 * A09 OWASP — All CRM data mutations are captured here.
 * DPDP: PII fields are redacted before write (AuditObserver).
 *
 * @property int         $id
 * @property string      $entity_type
 * @property int         $entity_id
 * @property string      $action         created|updated|deleted|restored
 * @property array|null  $old_values
 * @property array|null  $new_values
 * @property int|null    $user_id
 * @property int         $institution_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class AuditLog extends Model
{
    // Audit logs are append-only — never update or delete via Eloquent.
    public $timestamps = false;
    public $incrementing = true;

    protected $table = 'audit_logs';

    protected $casts = [
        'old_values'  => 'array',
        'new_values'  => 'array',
        'created_at'  => 'datetime',
    ];

    // Audit logs must never be mutated through the model.
    protected $guarded = ['*'];

    // ── Relationships ────────────────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    /** Filter to a specific institution. */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /** Filter to a specific entity (model + id). */
    public function scopeForEntity(Builder $query, string $entityType, int $entityId): Builder
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    /** Filter by action: created|updated|deleted|restored. */
    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /** Filter by actor (user who performed the action). */
    public function scopeByActor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
