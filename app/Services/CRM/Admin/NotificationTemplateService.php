<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Enums\CRM\Admin\NotificationChannel;
use App\Models\CRM\Admin\NotificationTemplate;

// BRD: CRM-SA-009 — Email and notification template management
class NotificationTemplateService
{
    public function create(array $data): NotificationTemplate
    {
        return NotificationTemplate::create($data);
    }

    public function update(NotificationTemplate $template, array $data): NotificationTemplate
    {
        $template->update($data);

        return $template->refresh();
    }

    public function delete(NotificationTemplate $template): void
    {
        $template->delete();
    }

    public function resolve(string $name, NotificationChannel $channel, array $mergeData): string
    {
        $template = NotificationTemplate::where('name', $name)
            ->where('channel', $channel->value)
            ->where('is_active', true)
            ->firstOrFail();

        $body = $template->body;

        foreach ($mergeData as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
            $body = str_replace('{{ '.$key.' }}', (string) $value, $body);
        }

        return $body;
    }
}
