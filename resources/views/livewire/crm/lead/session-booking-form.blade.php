{{-- BRD: CRM-EC-015 — Session booking form (Livewire) - embedded in lead show sessions tab --}}
<div class="p-5 space-y-4" wire:poll.20s>

    @if($successMessage)
        <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3" role="alert" wire:key="success">
            <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-medium text-green-800">{{ $successMessage }}</p>
        </div>
    @endif

    @if($errorMessage)
        <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3" role="alert" wire:key="error">
            <p class="text-sm font-medium text-red-800">{{ $errorMessage }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- Counsellor ID --}}
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-700">Counsellor User ID</label>
            <input type="number"
                   wire:model.live="counsellorId"
                   placeholder="e.g. 42"
                   class="input-field w-full"
                   min="1">
        </div>

        {{-- Session type --}}
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-700">Session Type</label>
            <select wire:model="sessionType" class="input-field w-full">
                @foreach($this->sessionTypes as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Date --}}
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-700">Date</label>
            <input type="date"
                   wire:model.live="date"
                   class="input-field w-full"
                   min="{{ today()->addDay()->toDateString() }}">
        </div>

        {{-- Time (computed from availability) --}}
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-700">Time</label>
            <select wire:model="time" class="input-field w-full">
                <option value="">Select time...</option>
                @foreach($this->availableTimes as $slot)
                    <option value="{{ $slot['time'] }}">{{ $slot['display'] }}</option>
                @endforeach
            </select>
            @if($counsellorId && $date && $this->availableTimes->isEmpty())
                <p class="mt-1 text-xs text-amber-600">No available slots for this date.</p>
            @endif
        </div>

        {{-- Mode --}}
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-700">Mode</label>
            <select wire:model="mode" class="input-field w-full">
                <option value="online">Online</option>
                <option value="offline">In-Person</option>
                <option value="phone">Phone</option>
            </select>
        </div>

    </div>

    {{-- Notes --}}
    <div>
        <label class="mb-1 block text-xs font-semibold text-gray-700">Pre-session Notes <span class="font-normal text-gray-400">(optional)</span></label>
        <textarea wire:model="notes"
                  rows="2"
                  class="input-field w-full resize-none"
                  placeholder="Any preparation notes or context..."></textarea>
    </div>

    {{-- Submit --}}
    <div class="flex justify-end">
        <button type="button"
                wire:click="book"
                wire:loading.attr="disabled"
                class="btn-primary-sm">
            <span wire:loading.remove>Book Session</span>
            <span wire:loading>Booking...</span>
        </button>
    </div>

</div>
