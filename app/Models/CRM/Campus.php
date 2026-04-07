<?php

declare(strict_types=1);

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'campuses';

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'city',
        'state',
        'is_active',
    ];

    /**
     * Override HasUuids to generate UUID for the 'uuid' column only.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
