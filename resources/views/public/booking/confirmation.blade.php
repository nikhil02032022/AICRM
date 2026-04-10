{{-- BRD: CRM-EC-016 — Booking confirmation page --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans antialiased">

    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md text-center">

            <div class="card p-8">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="mb-2 text-2xl font-bold text-gray-900">Session Booked!</h1>
                <p class="text-sm leading-relaxed text-gray-600">
                    Your counselling session has been scheduled. You will receive a confirmation and reminder notification closer to the date.
                </p>
                <p class="mt-4 text-xs text-gray-400">
                    If you need to reschedule, please contact our admissions team directly.
                </p>
            </div>

        </div>
    </div>

</body>
</html>
