<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Models\CRM\Admin\SystemConfig;

// BRD: CRM-SA-006 — System configuration (timezone, locale, branding, business hours)
class SystemConfigService
{
    public function get(string $key, int $institutionId, mixed $default = null): mixed
    {
        $config = SystemConfig::forInstitution($institutionId)->where('key', $key)->first();

        return $config ? $config->getValue() : $default;
    }

    public function set(string $key, mixed $value, string $type, int $institutionId): SystemConfig
    {
        $encoded = match($type) {
            'json'    => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default   => (string) $value,
        };

        return SystemConfig::updateOrCreate(
            ['institution_id' => $institutionId, 'key' => $key],
            ['value' => $encoded, 'type' => $type, 'updated_by' => auth()->id()]
        );
    }

    public function getGroup(string $prefix, int $institutionId): array
    {
        return SystemConfig::forInstitution($institutionId)
            ->where('key', 'like', $prefix.'%')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->key => $c->getValue()])
            ->all();
    }

    public function getAll(int $institutionId): array
    {
        return SystemConfig::forInstitution($institutionId)
            ->get()
            ->mapWithKeys(fn ($c) => [$c->key => $c->getValue()])
            ->all();
    }
}
