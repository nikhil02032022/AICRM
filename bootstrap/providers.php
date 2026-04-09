<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\CRM\CrmImportServiceProvider;
use App\Providers\CRM\CrmLeadServiceProvider;
use App\Providers\CRM\CrmScoringServiceProvider;
use App\Providers\CRM\CrmWebFormServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    CrmLeadServiceProvider::class,
    CrmWebFormServiceProvider::class,
    CrmImportServiceProvider::class,
    CrmScoringServiceProvider::class,
];
