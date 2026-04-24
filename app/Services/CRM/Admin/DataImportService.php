<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Models\CRM\Lead;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\LazyCollection;
use Spatie\SimpleExcel\SimpleExcelReader;

// BRD: CRM-SA-005 — Data import (leads, applications, contacts) in CSV/Excel
class DataImportService
{
    private const LEAD_REQUIRED_HEADERS = ['first_name', 'last_name', 'mobile', 'email', 'source'];

    public function validateHeaders(array $headers, string $entity): array
    {
        $required = match ($entity) {
            'leads'        => self::LEAD_REQUIRED_HEADERS,
            'applications' => ['lead_uuid', 'programme_id', 'status'],
            'contacts'     => ['first_name', 'last_name', 'mobile'],
            default        => [],
        };

        $missing = array_diff($required, $headers);

        return [
            'valid'  => empty($missing),
            'errors' => empty($missing) ? [] : ['Missing required columns: '.implode(', ', $missing)],
        ];
    }

    public function importLeads(UploadedFile $file, int $institutionId): array
    {
        $imported = 0;
        $errors   = [];

        $reader = SimpleExcelReader::create($file->getPathname());
        $headers = $reader->getHeaders() ?? [];

        $validation = $this->validateHeaders($headers, 'leads');
        if (! $validation['valid']) {
            return ['imported' => 0, 'errors' => $validation['errors']];
        }

        $row = 1;
        $reader->getRows()->each(function (array $rowData) use (
            $institutionId, &$imported, &$errors, &$row
        ) {
            $row++;
            $validator = Validator::make($rowData, [
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'mobile'     => 'required|string|max:20',
                'email'      => 'nullable|email|max:150',
                'source'     => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $row, 'message' => implode('; ', $validator->errors()->all())];

                return;
            }

            try {
                Lead::withoutGlobalScopes()->updateOrCreate(
                    ['institution_id' => $institutionId, 'mobile' => $rowData['mobile']],
                    array_merge($validator->validated(), ['institution_id' => $institutionId])
                );
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $row, 'message' => $e->getMessage()];
            }
        });

        return compact('imported', 'errors');
    }
}
