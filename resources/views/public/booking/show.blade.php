{{-- BRD: CRM-EC-016 — Public appointment booking page (no auth required) --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Book a Counselling Session</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans antialiased">

    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-lg">

            {{-- Header --}}
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-violet-600">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Book a Counselling Session</h1>
                <p class="mt-1 text-sm text-gray-500">Choose a convenient time to speak with a counsellor.</p>
            </div>

            {{-- Errors --}}
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                    <ul class="list-disc pl-4 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card p-6"
                 x-data="{ counsellorId: '{{ old('counsellor_id', $counsellorId ?: '') }}', date: '{{ old('date', $date) }}' }">

                <form method="POST" action="{{ route('public.booking.submit', $lead->uuid) }}">
                    @csrf
                    <div class="space-y-4">

                        {{-- Counsellor select --}}
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Select Counsellor</label>
                            <select name="counsellor_id"
                                    x-model="counsellorId"
                                    class="input-field w-full"
                                    required>
                                <option value="">Choose a counsellor...</option>
                                @foreach($activeSlots->unique('counsellor_id') as $slot)
                                    <option value="{{ $slot->counsellor_id }}"
                                            {{ old('counsellor_id', $counsellorId) == $slot->counsellor_id ? 'selected' : '' }}>
                                        {{ $slot->counsellor?->name ?? 'Counsellor #' . $slot->counsellor_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date --}}
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Preferred Date</label>
                            <input type="date"
                                   name="date"
                                   x-model="date"
                                   @change="$wire.set('date', date)"
                                   class="input-field w-full"
                                   min="{{ today()->addDay()->toDateString() }}"
                                   value="{{ old('date', $date) }}"
                                   required>
                        </div>

                        {{-- Time select --}}
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Preferred Time</label>
                            <select name="scheduled_at" class="input-field w-full" required>
                                <option value="">Select time...</option>
                                @foreach($availableTimes as $slot)
                                    <option value="{{ old('date', $date) }} {{ $slot['time'] }}"
                                            {{ old('scheduled_at') === (old('date', $date) . ' ' . $slot['time']) ? 'selected' : '' }}>
                                        {{ $slot['display'] }}
                                    </option>
                                @endforeach
                            </select>
                            @if($counsellorId && $availableTimes->isEmpty())
                                <p class="mt-1 text-xs text-amber-600">No slots available for this date. Please try another date.</p>
                            @endif
                        </div>

                        {{-- Session type --}}
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Session Type</label>
                            <select name="session_type" class="input-field w-full" required>
                                @foreach(\App\Enums\CRM\SessionType::cases() as $type)
                                    <option value="{{ $type->value }}"{{ old('session_type', 'initial') === $type->value ? ' selected' : '' }}>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Mode --}}
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Preferred Mode</label>
                            <select name="mode" class="input-field w-full" required>
                                <option value="online"{{ old('mode') === 'online' ? ' selected' : '' }}>Online (Video Call)</option>
                                <option value="phone"{{ old('mode') === 'phone' ? ' selected' : '' }}>Phone Call</option>
                                <option value="offline"{{ old('mode') === 'offline' ? ' selected' : '' }}>In-Person</option>
                            </select>
                        </div>

                        {{-- Reload button to refresh available times --}}
                        <div>
                            <a href="{{ route('public.booking.show', $lead->uuid) }}?counsellor_id={{ $counsellorId }}&date={{ $date }}"
                               class="text-xs text-primary-600 underline">
                                Reload available times for selected date
                            </a>
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center">
                            Confirm Booking
                        </button>

                    </div>
                </form>
            </div>

        </div>
    </div>

</body>
</html>
