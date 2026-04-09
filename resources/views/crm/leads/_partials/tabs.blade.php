                {{-- â”€â”€ Tabbed card â”€â”€ --}}
                <div class="card overflow-hidden p-0"
                     x-data="{ tab: 'timeline' }">

                    {{-- Tab strip --}}
                    <div class="flex overflow-x-auto border-b border-gray-100" role="tablist">
                        @foreach([
                            'timeline' => 'Timeline',
                            'scoring'  => 'Scoring',
                            'info'     => 'Contact Info',
                            'dpdp'     => 'DPDP',
                            'utm'      => 'UTM',
                        ] as $key => $label)
                        <button type="button"
                                role="tab"
                                :aria-selected="tab === '{{ $key }}'"
                                x-on:click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}'
                                    ? 'border-primary-600 text-primary-700'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap border-b-2 px-5 py-3 text-xs font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>


                    @include('crm.leads._partials.tab-timeline')
                    @include('crm.leads._partials.tab-scoring')
                    @include('crm.leads._partials.tab-info')
                    @include('crm.leads._partials.tab-dpdp')
                    @include('crm.leads._partials.tab-utm')

                </div>{{-- end tabbed card --}}
