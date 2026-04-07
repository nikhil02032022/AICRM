<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'institutions';

    protected $fillable = [
        'name',
        'code',
        'domain',
        'logo_path',
        'settings',
        'is_active',
    ];

    /**
     * Override HasUuids to generate UUID for the 'uuid' column,
     * keeping 'id' as the standard auto-incrementing integer primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
