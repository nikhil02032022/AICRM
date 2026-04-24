<?php

declare(strict_types=1);

namespace App\Models\CRM\Admin;

use App\Enums\CRM\Admin\BackupStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    protected $table = 'backup_logs';

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'filename',
        'disk',
        'size_bytes',
        'status',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status'       => BackupStatus::class,
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
            'size_bytes'   => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    public function formattedSize(): string
    {
        if ($this->size_bytes === null) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->size_bytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
