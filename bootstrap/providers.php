<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\CRM\CrmLeadServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    CrmLeadServiceProvider::class,
];
