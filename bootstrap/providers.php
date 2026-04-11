<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\CRM\CrmCommunicationServiceProvider;
use App\Providers\CRM\CrmCounsellingServiceProvider;
use App\Providers\CRM\CrmErpServiceProvider;
use App\Providers\CRM\CrmImportServiceProvider;
use App\Providers\CRM\CrmLeadServiceProvider;
use App\Providers\CRM\CrmMarketingServiceProvider;
use App\Providers\CRM\CrmScoringServiceProvider;
use App\Providers\CRM\CrmWebFormServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    CrmLeadServiceProvider::class,
    CrmWebFormServiceProvider::class,
    CrmMarketingServiceProvider::class,
    CrmImportServiceProvider::class,
    CrmScoringServiceProvider::class,
    CrmCounsellingServiceProvider::class,
    CrmCommunicationServiceProvider::class,
    CrmErpServiceProvider::class,
];
