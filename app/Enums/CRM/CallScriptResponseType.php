<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-TC-002 — Supported response payload types for script steps
enum CallScriptResponseType: string
{
    case TEXT = 'text';
    case SELECT = 'select';
    case BOOLEAN = 'boolean';
    case NUMBER = 'number';
}
