<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Repositories\CRM\Payments\EloquentPaymentReportRepository;
use App\Repositories\CRM\Payments\PaymentReportRepositoryInterface;
use App\Services\CRM\Payments\Gateways\PaymentGatewayInterface;
use App\Services\CRM\Payments\Gateways\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-FM-001 to CRM-FM-013 — Service container bindings for payments module
final class CrmPaymentServiceProvider extends ServiceProvider
{
    /** @var array<class-string,class-string> */
    public array $bindings = [
        PaymentReportRepositoryInterface::class => EloquentPaymentReportRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager($app, config('crm_payments.gateways', []));
        });

        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return $app->make(PaymentGatewayManager::class)
                ->driver(config('crm_payments.default_gateway'));
        });
    }

    public function boot(): void
    {
        // Policies and event wiring registered in AppServiceProvider where appropriate.
    }
}
