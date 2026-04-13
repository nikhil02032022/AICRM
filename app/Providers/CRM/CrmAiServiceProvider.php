<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Repositories\CRM\AI\EloquentQuestionnaireRepository;
use App\Repositories\CRM\AI\QuestionnaireRepositoryInterface;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LQ-009 — Service bindings for AI and advanced scoring module
final class CrmAiServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        QuestionnaireRepositoryInterface::class => EloquentQuestionnaireRepository::class,
    ];

    public function register(): void
    {
        // Bindings handled via $bindings.
    }

    public function boot(): void
    {
        // Gate policies for Group I will be added incrementally as modules are introduced.
    }
}
