<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EC-004 — All activity types that can appear on the lead's activity timeline
enum ActivityType: string
{
    case NOTE = 'note';
    case STATUS_CHANGE = 'status_change';
    case ASSIGNMENT = 'assignment';
    case CALL_LOGGED = 'call_logged';
    case EMAIL_SENT = 'email_sent';
    case WHATSAPP_SENT = 'whatsapp_sent';
    case SMS_SENT = 'sms_sent';
    case DOCUMENT_UPLOADED = 'document_uploaded';
    case PAYMENT_RECEIVED = 'payment_received';
    case SYSTEM = 'system';
    // BRD: CRM-LC-019 — Lead merge is a distinct, irreversible business event
    case MERGE = 'merge';

    public function label(): string
    {
        return match ($this) {
            self::NOTE => 'Note',
            self::STATUS_CHANGE => 'Status Changed',
            self::ASSIGNMENT => 'Assigned',
            self::CALL_LOGGED => 'Call Logged',
            self::EMAIL_SENT => 'Email Sent',
            self::WHATSAPP_SENT => 'WhatsApp Sent',
            self::SMS_SENT => 'SMS Sent',
            self::DOCUMENT_UPLOADED => 'Document Uploaded',
            self::PAYMENT_RECEIVED => 'Payment Received',
            self::SYSTEM => 'System',
            self::MERGE => 'Lead Merged',
        };
    }

    /** Heroicon outline name for timeline display */
    public function icon(): string
    {
        return match ($this) {
            self::NOTE => 'pencil-square',
            self::STATUS_CHANGE => 'arrow-path',
            self::ASSIGNMENT => 'user-plus',
            self::CALL_LOGGED => 'phone',
            self::EMAIL_SENT => 'envelope',
            self::WHATSAPP_SENT => 'chat-bubble-left-ellipsis',
            self::SMS_SENT => 'device-phone-mobile',
            self::DOCUMENT_UPLOADED => 'paper-clip',
            self::PAYMENT_RECEIVED => 'banknotes',
            self::SYSTEM => 'cog-6-tooth',
            self::MERGE => 'arrows-pointing-in',
        };
    }

    public function badgeColour(): string
    {
        return match ($this) {
            self::NOTE => 'indigo',
            self::STATUS_CHANGE => 'violet',
            self::ASSIGNMENT => 'blue',
            self::CALL_LOGGED => 'green',
            self::EMAIL_SENT => 'sky',
            self::WHATSAPP_SENT => 'emerald',
            self::SMS_SENT => 'teal',
            self::DOCUMENT_UPLOADED => 'amber',
            self::PAYMENT_RECEIVED => 'lime',
            self::SYSTEM => 'gray',
            self::MERGE => 'rose',
        };
    }

    /** Tailwind dot colour for timeline connector */
    public function dotColour(): string
    {
        return match ($this) {
            self::NOTE => '#6366F1',
            self::STATUS_CHANGE => '#8B5CF6',
            self::ASSIGNMENT => '#3B82F6',
            self::CALL_LOGGED => '#10B981',
            self::EMAIL_SENT => '#0EA5E9',
            self::WHATSAPP_SENT => '#059669',
            self::SMS_SENT => '#14B8A6',
            self::DOCUMENT_UPLOADED => '#F59E0B',
            self::PAYMENT_RECEIVED => '#84CC16',
            self::SYSTEM => '#9CA3AF',
            self::MERGE => '#F43F5E',
        };
    }
}
