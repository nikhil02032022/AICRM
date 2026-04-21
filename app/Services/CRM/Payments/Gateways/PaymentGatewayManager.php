<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Gateways;

use App\Enums\CRM\Payments\GatewayProvider;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

// BRD: CRM-FM-003 — Factory that resolves a payment gateway adapter by provider name.
final class PaymentGatewayManager
{
    /** @var array<string,PaymentGatewayInterface> */
    private array $resolved = [];

    /**
     * @param array<string,array<string,mixed>> $config
     */
    public function __construct(
        private readonly Application $app,
        private readonly array $config,
    ) {}

    public function driver(string|GatewayProvider $provider): PaymentGatewayInterface
    {
        $name = $provider instanceof GatewayProvider ? $provider->value : $provider;

        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $cfg = $this->config[$name] ?? null;
        if ($cfg === null) {
            throw new InvalidArgumentException("Payment gateway [$name] is not configured.");
        }

        $adapter = match ($name) {
            'razorpay' => new RazorpayGateway($cfg),
            'payu'     => new PayUGateway($cfg),
            'ccavenue' => new CCAvenueGateway($cfg),
            default    => throw new InvalidArgumentException("Unknown payment gateway driver [$name]."),
        };

        return $this->resolved[$name] = $adapter;
    }
}
