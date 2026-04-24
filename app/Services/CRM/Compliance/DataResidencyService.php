<?php

declare(strict_types=1);

namespace App\Services\CRM\Compliance;

use Illuminate\Support\Facades\App;

// BRD: CRM-CR-006 — All personal data of Indian residents on India-hosted servers
class DataResidencyService
{
    public function isCompliant(): bool
    {
        $env    = App::environment();
        $region = config('data_residency.storage_region', 'ap-south-1');
        $enforced = config('data_residency.enforce_in_environments', ['production', 'staging']);

        if (! in_array($env, $enforced)) {
            return true;
        }

        return $region === 'ap-south-1';
    }

    public function getConfig(): array
    {
        return [
            'storage_region'          => config('data_residency.storage_region'),
            'enforce_in_environments' => config('data_residency.enforce_in_environments'),
            'allowed_disks'           => config('data_residency.allowed_disks'),
            'is_compliant'            => $this->isCompliant(),
        ];
    }

    public function check(): bool
    {
        return $this->isCompliant();
    }
}
