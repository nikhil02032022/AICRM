<?php

namespace App\Enums\CRM\Compliance;

enum ConsentType: string
{
    case MarketingCommunication = 'marketing_communication';
    case DataProcessing         = 'data_processing';
    case CallRecording          = 'call_recording';

    public function label(): string
    {
        return match($this) {
            self::MarketingCommunication => 'Marketing Communication',
            self::DataProcessing         => 'Data Processing',
            self::CallRecording          => 'Call Recording',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::MarketingCommunication => 'badge-blue',
            self::DataProcessing         => 'badge-indigo',
            self::CallRecording          => 'badge-amber',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::MarketingCommunication => 'blue',
            self::DataProcessing         => 'indigo',
            self::CallRecording          => 'amber',
        };
    }
}
