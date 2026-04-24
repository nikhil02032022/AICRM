<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Lead;
use App\Observers\CRM\AI\LeadPredictionObserver;
use App\Policies\CRM\AI\AiPredictionPolicy;
use App\Repositories\CRM\AI\EloquentQuestionnaireRepository;
use App\Repositories\CRM\AI\QuestionnaireRepositoryInterface;
use App\Services\CRM\AI\ConversionPredictionService;
use App\Services\CRM\AI\LeadSignalAggregatorService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LQ-009, CRM-AI-001 — Service bindings for AI and advanced scoring module
final class CrmAiServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        QuestionnaireRepositoryInterface::class => EloquentQuestionnaireRepository::class,
    ];

    public function register(): void
    {
        // BRD: CRM-AI-001 — Singleton services for conversion prediction
        $this->app->singleton(LeadSignalAggregatorService::class);
        $this->app->singleton(ConversionPredictionService::class);
    }

    public function boot(): void
    {
        // BRD: CRM-AI-001 — Prediction policy and event-driven prediction refresh subscriber
        Gate::define('ai.prediction.view', static fn ($user, $lead) => (new AiPredictionPolicy)->viewPrediction($user, $lead));
        Gate::define('ai.prediction.feedback', static fn ($user, $lead) => (new AiPredictionPolicy)->feedback($user, $lead));

        Event::subscribe(LeadPredictionObserver::class);
    }
}
