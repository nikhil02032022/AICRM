<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Payments\StorePaymentLinkRequest;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Services\CRM\Payments\PaymentLinkService;
use Illuminate\Http\JsonResponse;

// BRD: CRM-FM-004 — Issue and share payment links via API.
class PaymentLinkController extends Controller
{
    public function __construct(private readonly PaymentLinkService $service) {}

    public function store(StorePaymentLinkRequest $request, PaymentTransaction $transaction): JsonResponse
    {
        $data = $request->validated();
        $link = $this->service->generate(
            $transaction,
            PaymentChannel::from($data['channel']),
            (string) $data['recipient'],
        );

        return response()->json([
            'data' => [
                'uuid'       => $link->uuid,
                'channel'    => $link->channel?->value,
                'expires_at' => $link->expires_at?->toIso8601String(),
            ],
        ], 201);
    }
}
