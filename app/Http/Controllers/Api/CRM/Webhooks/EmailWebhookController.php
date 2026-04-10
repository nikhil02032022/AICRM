<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Communication\ProcessEmailWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// BRD: CRM-CC-003 — Email delivery/open/bounce webhooks from providers
final class EmailWebhookController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['mailgun', 'sendgrid', 'ses'];

    public function __invoke(Request $request, string $provider): Response
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return response('', Response::HTTP_NOT_FOUND);
        }

        if (! $this->verifySignature($request, $provider)) {
            Log::warning('Email webhook signature mismatch', ['provider' => $provider]);
            return response('', Response::HTTP_FORBIDDEN);
        }

        // BRD: Return 200 immediately, process asynchronously (< 300ms)
        ProcessEmailWebhookJob::dispatch($provider, $request->all())
            ->onQueue('crm-comms-email');

        return response('', Response::HTTP_OK);
    }

    private function verifySignature(Request $request, string $provider): bool
    {
        $secret = config("services.email.{$provider}.webhook_secret");

        if (empty($secret)) {
            return false;
        }

        return match ($provider) {
            'mailgun'   => $this->verifyMailgunSignature($request, $secret),
            'sendgrid'  => $this->verifySendgridSignature($request, $secret),
            'ses'       => true, // SES uses SNS — verified in job via SNS cert
            default     => false,
        };
    }

    private function verifyMailgunSignature(Request $request, string $secret): bool
    {
        $timestamp = (string) $request->input('signature.timestamp', '');
        $token     = (string) $request->input('signature.token', '');
        $signature = (string) $request->input('signature.signature', '');

        $computed = hash_hmac('sha256', $timestamp . $token, $secret);

        return hash_equals($computed, $signature);
    }

    private function verifySendgridSignature(Request $request, string $secret): bool
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature', '');
        $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp', '');
        $computed  = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $secret, true));

        return hash_equals($computed, $signature);
    }
}
