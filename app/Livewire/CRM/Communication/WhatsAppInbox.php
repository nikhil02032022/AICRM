<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Communication;

use App\Models\CRM\WhatsAppConversation;
use App\Services\CRM\Communication\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

// BRD: CRM-CC-012 — WhatsApp inbox conversation list for counsellors
final class WhatsAppInbox extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function conversations()
    {
        return WhatsAppConversation::with(['lead', 'assignedCounsellor', 'latestMessage'])
            ->when($this->search, fn ($q) => $q->whereHas(
                'lead',
                fn ($l) => $l->where('name', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('last_activity_at')
            ->paginate(20);
    }

    #[On('conversation-updated')]
    public function refreshList(): void
    {
        unset($this->conversations);
    }

    public function render(): View
    {
        return view('livewire.crm.communication.whatsapp-inbox');
    }
}
