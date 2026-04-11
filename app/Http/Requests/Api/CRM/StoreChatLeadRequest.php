<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Http\Requests\Public\PublicChatLeadSubmissionRequest;

// BRD: CRM-LC-006 — Authenticated API variant for chat lead ingestion
final class StoreChatLeadRequest extends PublicChatLeadSubmissionRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.chat-widget.manage');
    }
}
