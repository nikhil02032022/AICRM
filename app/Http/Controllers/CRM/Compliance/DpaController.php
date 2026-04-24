<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Compliance;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

// BRD: CRM-CR-009 — Data Processing Agreement available for institutions
final class DpaController extends Controller
{
    public function show(): StreamedResponse
    {
        $this->authorize('crm.compliance.dpa.download');

        $path = storage_path('app/dpa/data-processing-agreement.pdf');

        if (! file_exists($path)) {
            $this->generatePlaceholderDpa($path);
        }

        return response()->streamDownload(function () use ($path) {
            readfile($path);
        }, 'data-processing-agreement.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function generatePlaceholderDpa(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Minimal placeholder — in production replace with a real DPA PDF
        $html = '<html><body><h1>Data Processing Agreement</h1>
            <p>This Data Processing Agreement is entered into between MEETCS Pvt. Ltd. (Data Processor)
            and the Institution (Data Fiduciary) as defined under the Digital Personal Data Protection Act, 2023.</p>
            <p>Version: 1.0 | Date: '.date('d M Y').'</p></body></html>';

        $pdf = new \Spipu\Html2Pdf\Html2Pdf();
        $pdf->writeHTML($html);
        $pdf->output($path, 'F');
    }
}
