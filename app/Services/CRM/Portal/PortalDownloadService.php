<?php

declare(strict_types=1);

namespace App\Services\CRM\Portal;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\OfferLetter;
use App\Models\CRM\Payments\PaymentTransaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Spipu\Html2Pdf\Html2Pdf;

// BRD: CRM-SP-005 — Portal PDF downloads: offer letter, admission confirmation, payment receipts
final class PortalDownloadService
{
    /**
     * Return a temporary S3 download URL for the applicant's offer letter PDF.
     *
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function offerLetterUrl(Lead $lead, string $applicationUuid, Institution $institution): string
    {
        $application = $this->resolveApplication($lead, $applicationUuid, $institution);

        $offerLetter = $application->currentOfferLetter;

        if ($offerLetter === null) {
            throw new \RuntimeException('No offer letter is available for this application.');
        }

        if (! $offerLetter->pdf_path) {
            throw new \RuntimeException('Your offer letter is being prepared. Please try again in a moment.');
        }

        return Storage::disk('s3')->temporaryUrl(
            $offerLetter->pdf_path,
            now()->addMinutes(15),
        );
    }

    /**
     * Generate admission confirmation letter PDF binary.
     * Only available after offer acceptance.
     *
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function admissionLetterPdf(Lead $lead, string $applicationUuid, Institution $institution): string
    {
        $application = $this->resolveApplication($lead, $applicationUuid, $institution);

        $offerLetter = $application->currentOfferLetter;

        if ($offerLetter === null || ! $offerLetter->isAccepted()) {
            throw new \RuntimeException('Admission confirmation is only available after offer acceptance.');
        }

        return $this->htmlToPdf(
            $this->buildAdmissionLetterHtml($lead, $application, $offerLetter, $institution)
        );
    }

    /**
     * Generate payment receipt PDF binary for a confirmed transaction.
     *
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function paymentReceiptPdf(Lead $lead, string $transactionUuid, Institution $institution): string
    {
        $transaction = PaymentTransaction::withoutGlobalScopes()
            ->where('uuid', $transactionUuid)
            ->where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->where('status', PaymentStatus::SUCCESS->value)
            ->first();

        if ($transaction === null) {
            throw new \RuntimeException('Receipt not available.');
        }

        // Confirm the application also belongs to this lead + institution
        $applicationOk = Application::withoutGlobalScopes()
            ->where('uuid', $transaction->application_uuid)
            ->where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->exists();

        if (! $applicationOk) {
            throw new \RuntimeException('Receipt not available.');
        }

        return $this->htmlToPdf($this->buildReceiptHtml($lead, $transaction, $institution));
    }

    /** @throws AuthorizationException */
    private function resolveApplication(Lead $lead, string $applicationUuid, Institution $institution): Application
    {
        $application = Application::withoutGlobalScopes()
            ->where('uuid', $applicationUuid)
            ->where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->with(['programme', 'currentOfferLetter'])
            ->first();

        if ($application === null) {
            throw new AuthorizationException('Application not found or access denied.');
        }

        return $application;
    }

    private function buildAdmissionLetterHtml(
        Lead $lead,
        Application $application,
        OfferLetter $offerLetter,
        Institution $institution,
    ): string {
        $programmeName   = e($application->programme?->name ?? 'the programme');
        $applicantName   = e($lead->full_name ?? $lead->first_name ?? 'Applicant');
        $institutionName = e($institution->name ?? 'Institution');
        $acceptedDate    = $offerLetter->acceptance_recorded_at?->format('d M Y') ?? now()->format('d M Y');
        $applicationRef  = strtoupper(substr($application->uuid, 0, 8));

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body>
            <h1 style="text-align:center;">{$institutionName}</h1>
            <h2 style="text-align:center;">Admission Confirmation Letter</h2>
            <p style="text-align:right;">Date: {$acceptedDate}</p>
            <br/>
            <p>Dear {$applicantName},</p>
            <p>
                We are pleased to confirm your admission to the programme
                <strong>{$programmeName}</strong> at {$institutionName}.
            </p>
            <p>Application Reference: <strong>{$applicationRef}</strong></p>
            <br/>
            <p>
                Please retain this letter as proof of your admission. You may be required
                to present this letter along with your original documents at enrolment.
            </p>
            <p>We look forward to welcoming you to our institution.</p>
            <br/><br/>
            <p>Yours sincerely,</p>
            <p><strong>The Admissions Office</strong><br/>{$institutionName}</p>
        </body>
        </html>
        HTML;
    }

    private function buildReceiptHtml(
        Lead $lead,
        PaymentTransaction $transaction,
        Institution $institution,
    ): string {
        $applicantName   = e($lead->full_name ?? $lead->first_name ?? 'Applicant');
        $institutionName = e($institution->name ?? 'Institution');
        $amount          = number_format((float) $transaction->amount, 2);
        $currency        = $transaction->currency ?? 'INR';
        $confirmedDate   = $transaction->confirmed_at?->format('d M Y H:i') ?? now()->format('d M Y H:i');
        $receiptRef      = strtoupper(substr($transaction->uuid, 0, 12));
        $gatewayRef      = e($transaction->gateway_payment_id ?? 'N/A');
        $feeType         = e($transaction->fee_type?->value ?? '-');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body>
            <h1 style="text-align:center;">{$institutionName}</h1>
            <h2 style="text-align:center;">Payment Receipt</h2>
            <hr/>
            <table width="100%" cellpadding="4">
                <tr><td><strong>Receipt No:</strong></td><td>{$receiptRef}</td></tr>
                <tr><td><strong>Date:</strong></td><td>{$confirmedDate}</td></tr>
            </table>
            <hr/>
            <table width="100%" cellpadding="4">
                <tr><td><strong>Received From:</strong></td><td>{$applicantName}</td></tr>
                <tr><td><strong>Email:</strong></td><td>{$lead->email}</td></tr>
            </table>
            <hr/>
            <table width="100%" cellpadding="4">
                <tr><td><strong>Description:</strong></td><td>{$feeType}</td></tr>
                <tr><td><strong>Amount:</strong></td><td>{$currency} {$amount}</td></tr>
                <tr><td><strong>Gateway Reference:</strong></td><td>{$gatewayRef}</td></tr>
                <tr><td><strong>Status:</strong></td><td><strong>PAID</strong></td></tr>
            </table>
            <hr/>
            <p style="text-align:center; font-size:10px;">
                This is a computer-generated receipt and does not require a physical signature.
            </p>
        </body>
        </html>
        HTML;
    }

    private function htmlToPdf(string $html): string
    {
        $pdf = new Html2Pdf('P', 'A4', 'en', true, 'UTF-8', [15, 15, 15, 15]);
        $pdf->setDefaultFont('Arial');
        $pdf->writeHTML($html);

        return $pdf->output('', 'S');
    }
}
