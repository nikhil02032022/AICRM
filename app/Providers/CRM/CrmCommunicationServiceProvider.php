<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Repositories\CRM\Communication\CommunicationLogRepositoryInterface;
use App\Repositories\CRM\Communication\CommunicationTemplateRepositoryInterface;
use App\Repositories\CRM\Communication\EloquentCommunicationLogRepository;
use App\Repositories\CRM\Communication\EloquentCommunicationTemplateRepository;
use App\Repositories\CRM\Communication\EloquentEmailCampaignRepository;
use App\Repositories\CRM\Communication\EmailCampaignRepositoryInterface;
use App\Services\CRM\Communication\BSP\GupshupBsp;
use App\Services\CRM\Communication\BSP\InteraktBsp;
use App\Services\CRM\Communication\BSP\MetaCloudBsp;
use App\Services\CRM\Communication\BSP\WhatsAppBspInterface;
use App\Services\CRM\Communication\Gateways\KaleyraGateway;
use App\Services\CRM\Communication\Gateways\Msg91Gateway;
use App\Services\CRM\Communication\Gateways\SmsGatewayInterface;
use App\Services\CRM\Communication\Gateways\TextlocalGateway;
use App\Services\CRM\Communication\Telephony\ExotelProvider;
use App\Services\CRM\Communication\Telephony\KnowlarityProvider;
use App\Services\CRM\Communication\Telephony\OzonetelProvider;
use App\Services\CRM\Communication\Telephony\TelephonyProviderInterface;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-CC-001 to CRM-CC-025 — Service container bindings for Group F Communication Engine
final class CrmCommunicationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        // Repositories
        CommunicationTemplateRepositoryInterface::class => EloquentCommunicationTemplateRepository::class,
        CommunicationLogRepositoryInterface::class      => EloquentCommunicationLogRepository::class,
        EmailCampaignRepositoryInterface::class         => EloquentEmailCampaignRepository::class,
    ];

    public function register(): void
    {
        // SMS Gateway — resolved from institution integration_credentials at runtime
        $this->app->bind(SmsGatewayInterface::class, function ($app) {
            $gateway = config('services.sms.default_gateway', 'msg91');

            return match ($gateway) {
                'textlocal' => new TextlocalGateway(
                    apiKey:   (string) config('services.sms.textlocal.api_key', ''),
                    senderId: (string) config('services.sms.textlocal.sender', ''),
                ),
                'kaleyra'   => new KaleyraGateway(
                    apiKey:   (string) config('services.sms.kaleyra.api_key', ''),
                    sid:      (string) config('services.sms.kaleyra.sid', ''),
                    senderId: (string) config('services.sms.kaleyra.sender_id', ''),
                ),
                default     => new Msg91Gateway(
                    apiKey:   (string) config('services.sms.msg91.auth_key', ''),
                    senderId: (string) config('services.sms.msg91.sender_id', ''),
                ),
            };
        });

        // WhatsApp BSP — resolved from institution integration_credentials at runtime
        $this->app->bind(WhatsAppBspInterface::class, function ($app) {
            $bsp = config('services.whatsapp.default_bsp', 'meta');

            return match ($bsp) {
                'interakt' => new InteraktBsp(
                    apiKey:        (string) config('services.whatsapp.interakt.api_key', ''),
                    webhookSecret: (string) config('services.whatsapp.interakt.webhook_secret', ''),
                ),
                'gupshup'  => new GupshupBsp(
                    apiKey:        (string) config('services.whatsapp.gupshup.api_key', ''),
                    appName:       (string) config('services.whatsapp.gupshup.app_name', ''),
                    sourcePhone:   (string) config('services.whatsapp.gupshup.source_phone', ''),
                    webhookSecret: (string) config('services.whatsapp.gupshup.webhook_secret', ''),
                ),
                default    => new MetaCloudBsp(
                        phoneNumberId: (string) config('services.whatsapp.meta.phone_number_id', ''),
                        accessToken:   (string) config('services.whatsapp.meta.access_token', ''),
                        appSecret:     (string) config('services.whatsapp.meta.app_secret', ''),
                    ),
            };
        });

        // Telephony provider — resolved from institution integration_credentials at runtime
        $this->app->bind(TelephonyProviderInterface::class, function ($app) {
            $provider = config('services.telephony.default_provider', 'exotel');

            return match ($provider) {
                'ozonetel'   => new OzonetelProvider(
                    apiKey: (string) config('services.telephony.ozonetel.api_key', ''),
                    userId: (string) config('services.telephony.ozonetel.username', ''),
                ),
                'knowlarity' => new KnowlarityProvider(
                    apiKey:      (string) config('services.telephony.knowlarity.api_key', ''),
                    accessToken: (string) config('services.telephony.knowlarity.auth_token', ''),
                    callerId:    (string) config('services.telephony.knowlarity.caller_id', ''),
                ),
                default      => new ExotelProvider(
                    apiKey:    (string) config('services.telephony.exotel.api_key', ''),
                    apiToken:  (string) config('services.telephony.exotel.api_token', ''),
                    accountSid: (string) config('services.telephony.exotel.sid', ''),
                ),
            };
        });
    }

    public function boot(): void
    {
        // No boot-time work needed; bindings are registered above.
    }
}
