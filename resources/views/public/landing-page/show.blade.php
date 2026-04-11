@php
    $themes = [
        'scholar' => [
            'heroShell' => 'from-sky-100 via-cyan-50 to-white',
            'haloA' => 'bg-sky-300/40',
            'haloB' => 'bg-cyan-300/35',
            'kicker' => 'text-sky-700',
            'badge' => 'border-sky-200 bg-white text-sky-900',
            'primaryCta' => 'bg-sky-700 text-white hover:bg-sky-800 focus:ring-sky-600',
            'secondaryCta' => 'border-sky-300 bg-white text-sky-900 hover:bg-sky-100 focus:ring-sky-600',
            'heroCard' => 'border-sky-200/80 bg-white/90',
            'metricBg' => 'bg-sky-50',
            'metricText' => 'text-sky-900',
            'sectionSurface' => 'bg-white',
            'sectionCard' => 'border-slate-200 bg-white hover:border-sky-300',
            'dotA' => 'bg-sky-600',
            'dotB' => 'bg-cyan-500',
        ],
        'sunrise' => [
            'heroShell' => 'from-amber-100 via-orange-50 to-white',
            'haloA' => 'bg-amber-300/45',
            'haloB' => 'bg-orange-300/35',
            'kicker' => 'text-amber-700',
            'badge' => 'border-amber-200 bg-white text-amber-900',
            'primaryCta' => 'bg-amber-700 text-white hover:bg-amber-800 focus:ring-amber-600',
            'secondaryCta' => 'border-amber-300 bg-white text-amber-900 hover:bg-amber-100 focus:ring-amber-600',
            'heroCard' => 'border-amber-200/80 bg-white/90',
            'metricBg' => 'bg-amber-50',
            'metricText' => 'text-amber-900',
            'sectionSurface' => 'bg-white',
            'sectionCard' => 'border-slate-200 bg-white hover:border-amber-300',
            'dotA' => 'bg-amber-700',
            'dotB' => 'bg-orange-600',
        ],
        'forest' => [
            'heroShell' => 'from-emerald-100 via-teal-50 to-white',
            'haloA' => 'bg-emerald-300/40',
            'haloB' => 'bg-teal-300/35',
            'kicker' => 'text-emerald-700',
            'badge' => 'border-emerald-200 bg-white text-emerald-900',
            'primaryCta' => 'bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-600',
            'secondaryCta' => 'border-emerald-300 bg-white text-emerald-900 hover:bg-emerald-100 focus:ring-emerald-600',
            'heroCard' => 'border-emerald-200/80 bg-white/90',
            'metricBg' => 'bg-emerald-50',
            'metricText' => 'text-emerald-900',
            'sectionSurface' => 'bg-white',
            'sectionCard' => 'border-slate-200 bg-white hover:border-emerald-300',
            'dotA' => 'bg-emerald-700',
            'dotB' => 'bg-teal-600',
        ],
    ];

    $theme = $themes[$landingPage->theme_variant] ?? $themes['scholar'];
    $sections = collect($landingPage->content ?? [])
        ->sortBy(static fn (array $section): int => (int) ($section['order'] ?? 999))
        ->values()
        ->filter(static function (array $section): bool {
            $type = (string) ($section['type'] ?? 'value_card');

            if ($type === 'stat') {
                return filled($section['metric_label'] ?? null) || filled($section['metric_value'] ?? null) || filled($section['body'] ?? null);
            }

            if ($type === 'faq') {
                return filled($section['question'] ?? null) || filled($section['answer'] ?? null);
            }

            return filled($section['title'] ?? null) || filled($section['body'] ?? null);
        });
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $landingPage->seo_title ?: $landingPage->name }}</title>
    <meta name="description" content="{{ $landingPage->seo_description ?: $landingPage->subheadline }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:500,600,700|public-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/public.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 antialiased [font-family:Public_Sans,sans-serif]">
    <main>
        <section class="relative overflow-hidden bg-gradient-to-br {{ $theme['heroShell'] }}">
            <div class="pointer-events-none absolute -left-20 top-0 h-72 w-72 rounded-full blur-3xl {{ $theme['haloA'] }} motion-safe:animate-pulse"></div>
            <div class="pointer-events-none absolute -right-24 bottom-0 h-72 w-72 rounded-full blur-3xl {{ $theme['haloB'] }} motion-safe:animate-pulse"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.02fr)_minmax(0,0.98fr)] lg:gap-10">
                    <div>
                        <p class="inline-flex min-h-11 items-center rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] {{ $theme['badge'] }}">
                            Your Admission Journey Starts Here
                        </p>

                        <h1 class="mt-6 max-w-3xl text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl lg:text-6xl [font-family:Space_Grotesk,sans-serif]">
                            {{ $landingPage->headline }}
                        </h1>

                        @if($landingPage->subheadline)
                            <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-700 sm:text-lg">
                                {{ $landingPage->subheadline }}
                            </p>
                        @endif

                        <div class="mt-8 flex flex-wrap gap-3">
                            @if($landingPage->webForm)
                                <a
                                    href="#lead-form"
                                    class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-full px-6 py-3 text-sm font-semibold shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $theme['primaryCta'] }}"
                                >
                                    {{ $landingPage->cta_label }}
                                </a>
                            @endif
                            @if($landingPage->cta_secondary_label)
                                <a
                                    href="#value-sections"
                                    class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-full border px-6 py-3 text-sm font-semibold transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $theme['secondaryCta'] }}"
                                >
                                    {{ $landingPage->cta_secondary_label }}
                                </a>
                            @endif
                        </div>

                    </div>

                    <aside class="grid gap-4" aria-label="Page overview">
                        <article class="rounded-[1.9rem] border p-5 shadow-xl shadow-slate-900/10 {{ $theme['heroCard'] }}">
                            @if($landingPage->hero_image_url)
                                <img
                                    src="{{ $landingPage->hero_image_url }}"
                                    alt="{{ $landingPage->name }} hero image"
                                    class="h-64 w-full rounded-[1.4rem] object-cover"
                                    loading="lazy"
                                >
                            @else
                                <div class="grid h-64 place-items-center rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-8 text-center">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">What You Can Expect</p>
                                        <p class="mt-3 text-2xl font-semibold leading-tight text-slate-900 [font-family:Space_Grotesk,sans-serif]">Fast capture. Clear narrative. Better conversions.</p>
                                    </div>
                                </div>
                            @endif

                        </article>
                    </aside>
                </div>
            </div>
        </section>

        @if($sections->isNotEmpty())
            <section id="value-sections" class="{{ $theme['sectionSurface'] }}">
                <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
                    <div class="mb-8 max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $theme['kicker'] }}">What You Get</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl [font-family:Space_Grotesk,sans-serif]">Everything you need to choose your next step with confidence.</h2>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($sections as $index => $section)
                            <article @class([
                                'rounded-3xl border p-6 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-md',
                                $theme['sectionCard'],
                                'sm:col-span-2' => $index === 0,
                                'xl:col-span-1' => $index === 0,
                            ])>
                                @if(($section['type'] ?? 'value_card') === 'stat')
                                    @if(!empty($section['metric_label']))
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $theme['kicker'] }}">{{ $section['metric_label'] }}</p>
                                    @endif
                                    @if(!empty($section['metric_value']))
                                        <p class="mt-3 text-4xl font-bold tracking-tight text-slate-900 [font-family:Space_Grotesk,sans-serif]">{{ $section['metric_value'] }}</p>
                                    @endif
                                    @if(!empty($section['body']))
                                        <p class="mt-4 text-base leading-relaxed text-slate-700">{{ $section['body'] }}</p>
                                    @endif
                                @elseif(($section['type'] ?? 'value_card') === 'faq')
                                    @if(!empty($section['question']))
                                        <h3 class="text-xl font-semibold tracking-tight text-slate-900 [font-family:Space_Grotesk,sans-serif]">{{ $section['question'] }}</h3>
                                    @endif
                                    @if(!empty($section['answer']))
                                        <p class="mt-4 text-base leading-relaxed text-slate-700">{{ $section['answer'] }}</p>
                                    @endif
                                @else
                                    @if(!empty($section['eyebrow']))
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $theme['kicker'] }}">{{ $section['eyebrow'] }}</p>
                                    @endif
                                    @if(!empty($section['title']))
                                        <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 [font-family:Space_Grotesk,sans-serif]">{{ $section['title'] }}</h3>
                                    @endif
                                    @if(!empty($section['body']))
                                        <p class="mt-4 text-base leading-relaxed text-slate-700">{{ $section['body'] }}</p>
                                    @endif
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section id="lead-form" class="bg-slate-100 text-slate-900">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,0.76fr)_minmax(0,1fr)]">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $theme['kicker'] }}">Get In Touch</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl [font-family:Space_Grotesk,sans-serif]">Share your details and our team will connect with you</h2>
                        <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-700">
                            Fill in a short form to receive guidance, fee details, and admission support from our counsellors.
                        </p>
                    </div>

                    <div class="rounded-[1.85rem] border border-slate-200 bg-white p-3 shadow-xl shadow-black/5">
                        @if($landingPage->formEmbedUrl())
                            <iframe
                                data-auto-resize-iframe
                                src="{{ $landingPage->formEmbedUrl() }}"
                                title="{{ $landingPage->webForm?->name ?? 'Enquiry form' }}"
                                class="h-[620px] w-full rounded-[1.35rem] border-0"
                                loading="lazy"
                            ></iframe>
                        @else
                            <div class="grid h-[420px] place-items-center rounded-[1.35rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                                <div>
                                    <p class="text-base font-semibold text-slate-900">Form setup is in progress</p>
                                    <p class="mt-2 text-sm leading-7 text-slate-600">Please check back shortly. This page will accept responses once setup is completed.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-600 sm:px-6 lg:px-8">
                <p class="font-medium text-slate-800">{{ $landingPage->name }}</p>
                <p>Built to help students discover programmes and request guidance in a simple, trusted flow.</p>
            </div>
        </section>
    </main>
</body>
</html>