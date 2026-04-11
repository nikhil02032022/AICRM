<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $institution->name }} Chatbot</title>
    <style>[x-cloak]{display:none !important;}</style>
    @vite(['resources/css/app.css', 'resources/js/public.js'])
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-100 via-cyan-50 to-white text-slate-900 antialiased">
    <div
        class="mx-auto w-full max-w-md p-3 sm:p-4"
        x-data="publicChatWidgetForm({
            submitUrl: @js(route('public.chat-widget.submit', ['institution' => $institution->uuid])),
            institutionName: @js($institution->name),
        })"
    >
        <div class="relative overflow-hidden rounded-3xl border border-cyan-100 bg-white shadow-[0_24px_70px_-30px_rgba(8,145,178,0.42)]">
            <div class="pointer-events-none absolute -right-12 -top-10 h-36 w-36 rounded-full bg-cyan-300/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-12 -left-10 h-36 w-36 rounded-full bg-emerald-200/40 blur-3xl"></div>

            <header class="relative border-b border-cyan-100 bg-gradient-to-r from-cyan-700 via-cyan-600 to-emerald-500 px-4 py-5 text-white sm:px-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-100">Website Admissions Chat</p>
                <h1 class="mt-1 text-lg font-semibold leading-tight">{{ $institution->name }}</h1>
                <p class="mt-1 text-xs text-cyan-100">Submit your details and one question. Our team will call you soon.</p>
            </header>

            <main class="relative px-4 py-4 sm:px-5">
                <div class="rounded-2xl border border-cyan-100 bg-cyan-50/80 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Quick Steps</p>
                    <ul class="mt-2 space-y-1 text-sm text-cyan-900">
                        <li>1. Fill your basic details.</li>
                        <li>2. Type one admission query.</li>
                        <li>3. Give consent and submit.</li>
                    </ul>
                </div>

                <form class="mt-4 space-y-3" @submit.prevent="submitLead" novalidate>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600" for="first_name">First Name <span class="text-rose-600">*</span></label>
                            <input id="first_name" type="text" x-model="lead.first_name" required maxlength="80"
                                   class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600" for="last_name">Last Name <span class="text-rose-600">*</span></label>
                            <input id="last_name" type="text" x-model="lead.last_name" required maxlength="80"
                                   class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600" for="mobile">Mobile <span class="text-rose-600">*</span></label>
                        <input id="mobile" type="tel" x-model="lead.mobile" maxlength="10" pattern="[6-9]\d{9}" required
                               class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600" for="email">Email</label>
                        <input id="email" type="email" x-model="lead.email" maxlength="160"
                               class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600" for="query_message">Your Query <span class="text-rose-600">*</span></label>
                        <textarea id="query_message" x-model="lead.query_message" rows="4" maxlength="1000" required
                                  class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                  placeholder="Example: Please share BCA eligibility, fee structure, and hostel details."></textarea>
                    </div>

                    <label class="flex items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                        <input type="checkbox" x-model="lead.consent_given" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                        <span>I consent to use of my personal data for admission counselling and follow-up communication. <span class="text-rose-600">*</span></span>
                    </label>

                    <p x-show="error" x-text="error" class="text-xs font-medium text-rose-600"></p>
                    <p x-show="success" x-text="success" class="text-xs font-medium text-emerald-700"></p>

                    <button type="submit" :disabled="submitting"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-cyan-800 focus:outline-none focus:ring-2 focus:ring-cyan-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                        Submit Enquiry
                    </button>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
