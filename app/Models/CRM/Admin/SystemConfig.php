<?php

declare(strict_types=1);

namespace App\Models\CRM\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemConfig extends Model
{
    protected $table = 'system_configs';

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'key',
        'value',
        'type',
        'updated_by',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function getValue(): mixed
    {
        return match($this->type) {
            'json'    => json_decode((string) $this->value, true),
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            default   => $this->value,
        };
    }

    public static function forInstitution(int $institutionId): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('institution_id', $institutionId);
    }
}
