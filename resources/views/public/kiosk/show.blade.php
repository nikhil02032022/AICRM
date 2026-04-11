<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $institution->name }} Walk-in Kiosk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>[x-cloak]{display:none !important;}</style>
    @vite(['resources/css/app.css', 'resources/js/public.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased [font-family:'Inter',sans-serif]">
    <div class="relative min-h-screen overflow-hidden" x-data="kioskCapture()">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(99,102,241,0.14),transparent_38%),radial-gradient(circle_at_bottom_right,rgba(124,58,237,0.1),transparent_40%)]"></div>

        <div class="relative mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                <header class="border-b border-gray-200 bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-7 text-white sm:px-8 sm:py-8">
                    <p class="inline-flex min-h-11 items-center rounded-full bg-white/15 px-4 text-xs font-semibold uppercase tracking-[0.2em]">
                        Walk-in Enquiry Kiosk
                    </p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">{{ $institution->name }}</h1>
                    <p class="mt-2 max-w-3xl text-base leading-relaxed text-indigo-100 sm:text-lg">
                        Share your admission query in less than a minute. Our counselling team will contact you shortly.
                    </p>
                </header>

                <main class="grid gap-0 lg:grid-cols-12">
                    <aside class="border-b border-gray-200 bg-gray-50 p-5 lg:col-span-4 lg:border-b-0 lg:border-r lg:p-7">
                        <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-gray-700">How It Works</h2>
                        <ol class="mt-4 space-y-3">
                            <li class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3">
                                <span class="inline-flex h-7 w-7 flex-none items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">1</span>
                                <p class="pt-0.5 text-sm text-gray-700">Fill your basic details and active mobile number.</p>
                            </li>
                            <li class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3">
                                <span class="inline-flex h-7 w-7 flex-none items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">2</span>
                                <p class="pt-0.5 text-sm text-gray-700">Write your programme or admission query clearly.</p>
                            </li>
                            <li class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3">
                                <span class="inline-flex h-7 w-7 flex-none items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">3</span>
                                <p class="pt-0.5 text-sm text-gray-700">Provide consent and submit the enquiry.</p>
                            </li>
                        </ol>

                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Privacy First</p>
                            <p class="mt-1 text-sm leading-relaxed text-amber-900">Your information is used only for admission counselling and follow-up communication.</p>
                        </div>
                    </aside>

                    <section class="p-5 sm:p-7 lg:col-span-8">
                        <form class="space-y-5" @submit.prevent="submitLead" novalidate>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="first_name" class="block text-sm font-semibold text-gray-700">First Name <span class="text-red-600">*</span></label>
                                    <input id="first_name" type="text" x-model="lead.first_name" maxlength="80" required
                                           class="mt-1 block min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-semibold text-gray-700">Last Name <span class="text-red-600">*</span></label>
                                    <input id="last_name" type="text" x-model="lead.last_name" maxlength="80" required
                                           class="mt-1 block min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="mobile" class="block text-sm font-semibold text-gray-700">Mobile Number <span class="text-red-600">*</span></label>
                                    <input id="mobile" type="tel" x-model="lead.mobile" maxlength="10" pattern="[6-9]\d{9}" required
                                           class="mt-1 block min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-700">Email Address</label>
                                    <input id="email" type="email" x-model="lead.email" maxlength="160"
                                           class="mt-1 block min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label for="query_message" class="block text-sm font-semibold text-gray-700">Admission Query <span class="text-red-600">*</span></label>
                                <textarea id="query_message" x-model="lead.query_message" rows="5" maxlength="1000" required
                                          class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Example: I need BBA admission details for July intake and scholarship eligibility."></textarea>
                            </div>

                            <div>
                                <label for="kiosk_label" class="block text-sm font-semibold text-gray-700">Kiosk Label</label>
                                <input id="kiosk_label" type="text" x-model="lead.kiosk_label" maxlength="100"
                                       class="mt-1 block min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       placeholder="Example: Campus Reception Desk">
                            </div>

                            <label class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm leading-relaxed text-gray-700">
                                <input type="checkbox" x-model="lead.consent_given" required class="mt-1 h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>I consent to the use of my personal data for admission counselling and follow-up communication. <span class="text-red-600">*</span></span>
                            </label>

                            <div aria-live="polite" class="min-h-6">
                                <p x-show="error" x-text="error" class="text-sm font-semibold text-red-700"></p>
                                <p x-show="success" x-text="success" class="text-sm font-semibold text-green-700" x-transition.opacity.duration.200ms></p>
                            </div>

                            <button type="submit" :disabled="submitting"
                                    class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-base font-semibold text-white transition-colors duration-150 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="!submitting" x-cloak>Submit Walk-in Enquiry</span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-30"></circle>
                                        <path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    </svg>
                                    Submitting...
                                </span>
                            </button>
                        </form>
                    </section>
                </main>
            </div>
        </div>
    </div>

    <script>
    function kioskCapture() {
        return {
            lead: {
                first_name: '',
                last_name: '',
                mobile: '',
                email: '',
                query_message: '',
                kiosk_label: '',
                consent_given: false,
                consent_form_version: 'kiosk-v1',
            },
            submitting: false,
            error: '',
            success: '',

            async submitLead() {
                this.error = '';
                this.success = '';
                this.submitting = true;

                try {
                    const response = await fetch('{{ route('public.kiosk.submit', ['institution' => $institution->uuid]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(this.lead),
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        this.success = 'Thank you. Your enquiry has been registered.';
                        this.lead.first_name = '';
                        this.lead.last_name = '';
                        this.lead.mobile = '';
                        this.lead.email = '';
                        this.lead.query_message = '';
                        this.lead.consent_given = false;
                    } else if (response.status === 422) {
                        this.error = 'Please review your details and consent, then try again.';
                    } else {
                        this.error = data.message || 'Unable to submit at the moment.';
                    }
                } catch (error) {
                    this.error = 'Network error. Please try again.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
    </script>
</body>
</html>