<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Communication;

use App\Services\CRM\Communication\UnifiedInboxService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

// BRD: CRM-CC-021 — Unified inbox: WhatsApp + Email + SMS in one view
final class UnifiedInbox extends Component
{
    public string $activeTab = 'whatsapp';
    public string $search = '';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->search = '';
    }

    #[Computed]
    public function unreadCounts(): array
    {
        return app(UnifiedInboxService::class)->getUnreadCounts(Auth::user());
    }

    #[Computed]
    public function inbox(): LengthAwarePaginator
    {
        return app(UnifiedInboxService::class)->getInboxForCounsellor(
            Auth::user(),
            ['channel' => $this->activeTab, 'search' => $this->search],
        );
    }

    #[On('inbox-updated')]
    public function refresh(): void
    {
        unset($this->inbox, $this->unreadCounts);
    }

    public function render(): View
    {
        return view('livewire.crm.communication.unified-inbox');
    }
}
