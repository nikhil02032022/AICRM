<?php

declare(strict_types=1);

namespace App\Observers\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * AuditObserver — Writes all CRM model mutations to audit_logs.
 *
 * A09 OWASP — All CRM data mutations are logged.
 * DPDP: PII fields are excluded from old_values/new_values before write.
 *
 * Attach to any CRM model via:
 *   protected static function booted(): void
 *   {
 *       static::observe(AuditObserver::class);
 *   }
 */
class AuditObserver
{
    /**
     * PII field names that must NEVER appear in audit log values.
     * BRD: No PII in logs.
     *
     * @var array<int, string>
     */
    private const PII_FIELDS = [
        'mobile',
        'email',
        'aadhaar',
        'aadhaar_hash',
        'pan_number',
        'passport_number',
        'dob',
        'address',
        'phone',
        'guardian_mobile',
        'guardian_email',
    ];

    public function created(Model $model): void
    {
        $this->log($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'updated', $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getAttributes(), []);
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored', [], $model->getAttributes());
    }

    private function log(Model $model, string $action, array $oldValues, array $newValues): void
    {
        DB::table('audit_logs')->insert([
            'entity_type' => $model::class,
            'entity_id' => $model->getKey(),
            'action' => $action,
            'old_values' => json_encode($this->scrubPii($oldValues)),
            'new_values' => json_encode($this->scrubPii($newValues)),
            'user_id' => Auth::id(),
            'institution_id' => $this->resolveInstitutionId($model),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Remove PII fields from values before storing in audit log.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function scrubPii(array $values): array
    {
        foreach (self::PII_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }

    private function resolveInstitutionId(Model $model): int
    {
        if (isset($model->institution_id)) {
            return (int) $model->institution_id;
        }

        if (Auth::check() && Auth::user()?->institution_id !== null) {
            return (int) Auth::user()->institution_id;
        }

        return 0;
    }
}
