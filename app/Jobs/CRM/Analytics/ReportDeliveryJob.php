<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Analytics;

use App\Enums\CRM\ReportDeliveryStatus;
use App\Enums\CRM\ReportFormat;
use App\Models\CRM\ReportDelivery;
use App\Services\CRM\Analytics\CustomReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

// BRD: CRM-AR-020 — Async job that generates and emails a scheduled report
final class ReportDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $deliveryId,
    ) {}

    public function handle(CustomReportService $reportService): void
    {
        /** @var ReportDelivery $delivery */
        $delivery = ReportDelivery::with('customReport', 'schedule')->findOrFail($this->deliveryId);

        $delivery->update(['status' => ReportDeliveryStatus::SENDING]);

        try {
            // BRD: CRM-CR-002 — No PII in logs; use IDs only
            Log::info('Generating scheduled report delivery', [
                'delivery_id'     => $delivery->id,
                'custom_report_id'=> $delivery->custom_report_id,
                'format'          => $delivery->format->value,
            ]);

            $result = $reportService->run($delivery->customReport);

            // Generate export file in memory and attach to email
            $fileContent = $this->generateFileContent($result, $delivery->format);
            $filename    = $this->buildFilename($delivery->customReport->name, $delivery->format);

            foreach ($delivery->recipient_emails as $email) {
                Mail::raw(
                    "Please find your scheduled report '{$delivery->customReport->name}' attached.",
                    function ($message) use ($email, $fileContent, $filename, $delivery): void {
                        $message->to($email)
                            ->subject("Scheduled Report: {$delivery->customReport->name}")
                            ->attachData($fileContent, $filename);
                    }
                );
            }

            // BRD: CRM-AR-020 — Record export for DPDP audit trail
            $reportService->recordExport(
                $delivery->customReport,
                0, // system-generated, no user id
                $delivery->format->value,
                $result['total'],
                null,
            );

            $delivery->update([
                'status'  => ReportDeliveryStatus::SENT,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Report delivery failed', [
                'delivery_id' => $delivery->id,
                'error'       => $e->getMessage(),
            ]);

            $delivery->update([
                'status'        => ReportDeliveryStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /** @param array{headers: list<string>, rows: list<array<string, mixed>>, total: int} $result */
    private function generateFileContent(array $result, ReportFormat $format): string
    {
        return match ($format) {
            ReportFormat::CSV   => $this->toCsv($result['headers'], $result['rows']),
            ReportFormat::EXCEL,
            ReportFormat::PDF   => $this->toCsv($result['headers'], $result['rows']), // placeholder; extend with Spatie/PhpSpreadsheet
        };
    }

    /**
     * @param list<string>            $headers
     * @param list<array<string, mixed>> $rows
     */
    private function toCsv(array $headers, array $rows): string
    {
        $output = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $output .= implode(',', array_map(
                fn ($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"',
                array_values($row),
            )) . "\n";
        }

        return $output;
    }

    private function buildFilename(string $reportName, ReportFormat $format): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $reportName);

        return "{$safe}_" . now()->format('Ymd_His') . '.' . $format->value;
    }
}
