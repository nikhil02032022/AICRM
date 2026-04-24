<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Communication;

use App\Models\CRM\CallLog;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

// BRD: CRM-AI-007 — Real-time transcription status panel with AI summary chips and retry action
final class TranscriptionPanel extends Component
{
    #[Locked]
    public string $callLogUuid;

    public function mount(string $callLogUuid): void
    {
        $this->callLogUuid = $callLogUuid;
        $this->authorize('crm.communication.send');
    }

    #[Computed]
    public function callLog(): CallLog
    {
        return CallLog::withoutGlobalScopes()
            ->where('uuid', $this->callLogUuid)
            ->firstOrFail();
    }

    public function getListeners(): array
    {
        $status = $this->callLog->transcription_status;

        // Poll every 10 seconds while transcription is in a non-terminal state
        if ($status !== null && ! $status->isTerminal()) {
            return ['$refresh' => '$refresh'];
        }

        return [];
    }

    public function render(): View
    {
        unset($this->callLog);

        return view('livewire.crm.communication.transcription-panel');
    }
}
