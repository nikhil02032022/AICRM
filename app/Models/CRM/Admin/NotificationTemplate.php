<?php

declare(strict_types=1);

namespace App\Models\CRM\Admin;

use App\Enums\CRM\Admin\NotificationChannel;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(AuditObserver::class)]
class NotificationTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'notification_templates';

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'channel',
        'name',
        'subject',
        'body',
        'merge_tags_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channel'         => NotificationChannel::class,
            'merge_tags_json' => 'array',
            'is_active'       => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }
}
