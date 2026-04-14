<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Repositories\CRM\Agent\AgentCommissionRepositoryInterface;
use App\Repositories\CRM\Agent\AgentCommsRepositoryInterface;
use App\Repositories\CRM\Agent\EloquentAgentCommissionRepository;
use App\Repositories\CRM\Agent\EloquentAgentCommsRepository;
use App\Repositories\CRM\Integration\AadhaarRepositoryInterface;
use App\Repositories\CRM\Integration\AlumniBridgeRepositoryInterface;
use App\Repositories\CRM\Integration\DigiLockerRepositoryInterface;
use App\Repositories\CRM\Integration\EloquentAadhaarRepository;
use App\Repositories\CRM\Integration\EloquentAlumniBridgeRepository;
use App\Repositories\CRM\Integration\EloquentDigiLockerRepository;
use App\Repositories\CRM\Integration\EloquentLmsEnrolmentRepository;
use App\Repositories\CRM\Integration\LmsEnrolmentRepositoryInterface;
use Illuminate\Support\ServiceProvider;

// BRD: DM-006, DM-007, EI-008, EI-010, AG-006, AG-008 — Group L service bindings
final class CrmIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Integration repositories
        $this->app->bind(
            DigiLockerRepositoryInterface::class,
            EloquentDigiLockerRepository::class,
        );

        $this->app->bind(
            AadhaarRepositoryInterface::class,
            EloquentAadhaarRepository::class,
        );

        $this->app->bind(
            AlumniBridgeRepositoryInterface::class,
            EloquentAlumniBridgeRepository::class,
        );

        $this->app->bind(
            LmsEnrolmentRepositoryInterface::class,
            EloquentLmsEnrolmentRepository::class,
        );

        // Agent repositories
        $this->app->bind(
            AgentCommissionRepositoryInterface::class,
            EloquentAgentCommissionRepository::class,
        );

        $this->app->bind(
            AgentCommsRepositoryInterface::class,
            EloquentAgentCommsRepository::class,
        );
    }
}
