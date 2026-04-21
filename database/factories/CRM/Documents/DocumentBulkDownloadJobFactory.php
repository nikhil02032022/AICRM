<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Documents;

use App\Enums\CRM\Documents\BulkDownloadStatus;
use App\Models\CRM\Documents\DocumentBulkDownloadJob;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DocumentBulkDownloadJob> */
class DocumentBulkDownloadJobFactory extends Factory
{
    protected $model = DocumentBulkDownloadJob::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'requested_by'   => User::factory(),
            'scope'          => 'application',
            'target_ref'     => (string) Str::uuid(),
            'status'         => BulkDownloadStatus::QUEUED->value,
        ];
    }
}
