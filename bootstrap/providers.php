<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\CRM\CrmCommunicationServiceProvider;
use App\Providers\CRM\CrmCounsellingServiceProvider;
use App\Providers\CRM\CrmCustomisationServiceProvider;
use App\Providers\CRM\CrmApplicationServiceProvider;
use App\Providers\CRM\CrmErpServiceProvider;
use App\Providers\CRM\CrmIntegrationServiceProvider;
use App\Providers\CRM\CrmAiServiceProvider;
use App\Providers\CRM\CrmImportServiceProvider;
use App\Providers\CRM\CrmLeadServiceProvider;
use App\Providers\CRM\CrmMarketingServiceProvider;
use App\Providers\CRM\CrmDocumentServiceProvider;
use App\Providers\CRM\CrmAgentServiceProvider;
use App\Providers\CRM\CrmAnalyticsServiceProvider;
use App\Providers\CRM\CrmTaskServiceProvider;
use App\Providers\CRM\CrmPaymentServiceProvider;
use App\Providers\CRM\CrmScholarshipServiceProvider;
use App\Providers\CRM\CrmScoringServiceProvider;
use App\Providers\CRM\CrmWebFormServiceProvider;
use App\Providers\CRM\CrmAdminServiceProvider;
use App\Providers\CRM\CrmComplianceServiceProvider;
use App\Providers\CRM\CrmAlumniServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    CrmApplicationServiceProvider::class,
    CrmLeadServiceProvider::class,
    CrmWebFormServiceProvider::class,
    CrmMarketingServiceProvider::class,
    CrmImportServiceProvider::class,
    CrmScoringServiceProvider::class,
    CrmAiServiceProvider::class,
    CrmCounsellingServiceProvider::class,
    CrmCommunicationServiceProvider::class,
    CrmCustomisationServiceProvider::class,
    CrmErpServiceProvider::class,
    CrmIntegrationServiceProvider::class,
    CrmPaymentServiceProvider::class,
    CrmScholarshipServiceProvider::class,
    CrmDocumentServiceProvider::class,
    CrmTaskServiceProvider::class,
    CrmAgentServiceProvider::class,
    CrmAnalyticsServiceProvider::class,
    CrmAdminServiceProvider::class,
    CrmComplianceServiceProvider::class,
    CrmAlumniServiceProvider::class,
];
