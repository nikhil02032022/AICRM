<?php

declare(strict_types=1);

namespace App\Services\CRM\Compliance;

use App\Enums\CRM\Admin\NotificationChannel;
use App\Models\CRM\Admin\NotificationTemplate;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-CR-008 — SMS communications must use DLT-registered templates
class DltTemplateValidatorService
{
    public function getRegisteredTemplates(): Collection
    {
        return NotificationTemplate::withoutGlobalScopes()
            ->where('channel', NotificationChannel::SMS->value)
            ->where('is_active', true)
            ->get();
    }

    public function isRegistered(string $content): bool
    {
        // Checks if the content matches any registered SMS template body (fuzzy match by key phrases)
        return $this->getRegisteredTemplates()->contains(function (NotificationTemplate $t) use ($content) {
            return str_contains(strtolower($content), strtolower(substr($t->body, 0, 30)));
        });
    }

    public function validate(string $templateContent, string $senderId): bool
    {
        return ! empty($senderId) && strlen($templateContent) <= 160 && $this->isRegistered($templateContent);
    }
}
