<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Lead;

use App\DTOs\CRM\BookSessionDTO;
use App\Enums\CRM\SessionType;
use App\Services\CRM\Counselling\CounsellingService;
use App\Services\CRM\Counselling\CounsellorAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

// BRD: CRM-EC-015 — Reactive session booking form on the lead detail page
final class SessionBookingForm extends Component
{
    public string $leadUuid = '';

    public int $leadId = 0;

    public int $counsellorId = 0;

    public string $date = '';

    public string $time = '';

    public string $sessionType = '';

    public string $mode = 'online';

    public string $notes = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(string $leadUuid, int $leadId): void
    {
        $this->leadUuid = $leadUuid;
        $this->leadId = $leadId;
        $this->date = today()->addDay()->toDateString();
        $this->sessionType = SessionType::INITIAL->value;
    }

    /** @return Collection<int, array{time: string, display: string}> */
    #[Computed]
    public function availableTimes(): Collection
    {
        if (!$this->counsellorId || !$this->date) {
            return collect();
        }

        /** @var CounsellorAvailabilityService $svc */
        $svc = app(CounsellorAvailabilityService::class);

        return $svc->getAvailableTimesForDate($this->counsellorId, Carbon::parse($this->date));
    }

    /** @return array<string, string> */
    #[Computed]
    public function sessionTypes(): array
    {
        return collect(SessionType::cases())
            ->mapWithKeys(fn (SessionType $t) => [$t->value => $t->label()])
            ->all();
    }

    public function book(): void
    {
        $this->validate([
            'counsellorId' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'sessionType' => ['required', 'string'],
            'mode' => ['required', 'in:online,offline,phone'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            /** @var CounsellingService $svc */
            $svc = app(CounsellingService::class);

            $svc->book(new BookSessionDTO(
                leadId: $this->leadId,
                counsellorId: $this->counsellorId,
                sessionType: SessionType::from($this->sessionType),
                scheduledAt: Carbon::parse("{$this->date} {$this->time}"),
                mode: $this->mode,
                preSessionNotes: $this->notes ?: null,
            ));

            $this->successMessage = 'Session booked successfully.';
            $this->errorMessage = null;
            $this->reset(['time', 'notes']);
            $this->dispatch('session-booked');
        } catch (\Throwable $e) {
            $this->errorMessage = 'Could not book the session. Please try again.';
            $this->successMessage = null;
        }
    }

    public function render(): View
    {
        return view('livewire.crm.lead.session-booking-form');
    }
}
