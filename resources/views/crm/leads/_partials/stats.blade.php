                {{-- Mini stats row â€” 4 tiles matching mockup --}}
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">

                    {{-- Touchpoints --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px] bg-primary-500"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Touchpoints</p>
                            <p class="mt-2 text-2xl font-bold leading-none text-gray-900">{{ $touchpoints }}</p>
                        </div>
                    </div>

                    {{-- Days Active --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px] bg-green-500"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Days Active</p>
                            <p class="mt-2 text-2xl font-bold leading-none text-gray-900">{{ $daysActive }}</p>
                        </div>
                    </div>

                    {{-- AI Score --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px]" style="background-color:{{ $scoreColour }}"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">AI Score</p>
                            <p class="mt-2 text-2xl font-bold leading-none" style="color:{{ $scoreColour }}">{{ $lead->lead_score }}</p>
                        </div>
                    </div>

                    {{-- Consent --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px] bg-violet-500"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Consent</p>
                            <p class="mt-2 text-2xl font-bold leading-none {{ $lead->consent_given ? 'text-green-600' : 'text-red-500' }}">{{ $lead->consent_given ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>

                </div>

