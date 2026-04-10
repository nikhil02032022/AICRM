<x-layouts.crm title="Communication Templates">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Communication Templates</h1>
                <p class="mt-1 text-sm text-gray-500">Manage email, SMS, and WhatsApp message templates</p>
            </div>
            @can('crm.communication.templates.manage')
            <a href="{{ route('crm.communication.templates.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Template
            </a>
            @endcan
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full">
                {{-- ── Header ── --}}
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Template</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-28">Channel</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-32">Type</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Subject / Body Preview</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-36">Created</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 w-40">Actions</th>
                    </tr>
                </thead>

                {{-- ── Body ── --}}
                <tbody class="divide-y divide-gray-100">
                    @forelse ($templates as $template)
                    @php
                        $channelColors = match($template->channel->value) {
                            'EMAIL'    => ['dot' => 'bg-indigo-400', 'text' => 'text-indigo-700', 'bg' => 'bg-indigo-50'],
                            'SMS'      => ['dot' => 'bg-blue-400',   'text' => 'text-blue-700',   'bg' => 'bg-blue-50'],
                            'WHATSAPP' => ['dot' => 'bg-green-400',  'text' => 'text-green-700',  'bg' => 'bg-green-50'],
                            default    => ['dot' => 'bg-gray-400',   'text' => 'text-gray-600',   'bg' => 'bg-gray-50'],
                        };
                        $typeColors = match($template->type->value) {
                            'TRANSACTIONAL' => ['dot' => 'bg-emerald-400', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
                            'MARKETING'     => ['dot' => 'bg-violet-400',  'text' => 'text-violet-700',  'bg' => 'bg-violet-50'],
                            'OTP'           => ['dot' => 'bg-orange-400',  'text' => 'text-orange-700',  'bg' => 'bg-orange-50'],
                            'NOTIFICATION'  => ['dot' => 'bg-amber-400',   'text' => 'text-amber-700',   'bg' => 'bg-amber-50'],
                            default         => ['dot' => 'bg-gray-400',    'text' => 'text-gray-600',    'bg' => 'bg-gray-50'],
                        };
                    @endphp
                    <tr class="group hover:bg-gray-50/70 transition-colors duration-100">

                        {{-- Template name + language sub-line --}}
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $template->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Language:&nbsp;<span class="font-medium text-gray-500">{{ strtoupper($template->language ?? 'EN') }}</span>
                                @if($template->is_active)
                                    &nbsp;·&nbsp;<span class="text-emerald-600 font-medium">Active</span>
                                @else
                                    &nbsp;·&nbsp;<span class="text-gray-400">Inactive</span>
                                @endif
                            </p>
                        </td>

                        {{-- Channel --}}
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $channelColors['bg'] }} {{ $channelColors['text'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $channelColors['dot'] }}" aria-hidden="true"></span>
                                {{ $template->channel->value }}
                            </span>
                        </td>

                        {{-- Type --}}
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $typeColors['bg'] }} {{ $typeColors['text'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $typeColors['dot'] }}" aria-hidden="true"></span>
                                {{ $template->type->label() }}
                            </span>
                        </td>

                        {{-- Subject / Body preview --}}
                        <td class="px-4 py-4 max-w-xs">
                            @if ($template->channel->value === 'EMAIL' && $template->subject)
                                <p class="truncate text-xs text-gray-500">
                                    <span class="mr-1.5 inline-block rounded bg-gray-100 px-1 py-px text-[10px] font-semibold uppercase tracking-widest text-gray-400">subj</span>{{ Str::limit($template->subject, 52) }}
                                </p>
                            @else
                                <p class="truncate text-xs text-gray-500 italic">{{ Str::limit($template->body_text, 65) }}</p>
                            @endif
                        </td>

                        {{-- Created --}}
                        <td class="px-4 py-4 text-center">
                            <span class="text-xs text-gray-400">{{ $template->created_at->diffForHumans() }}</span>
                        </td>

                        {{-- Actions: icon+text links with pipe dividers --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-0 divide-x divide-gray-200">

                                <a href="{{ route('crm.communication.templates.edit', $template->uuid) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded"
                                   aria-label="Edit {{ $template->name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                    </svg>
                                    Edit
                                </a>

                                {{-- Hidden delete form --}}
                                <form id="form-del-tpl-{{ $template->uuid }}"
                                      method="POST"
                                      action="{{ route('crm.communication.templates.destroy', $template->uuid) }}"
                                      class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                                @can('crm.communication.templates.manage')
                                <button type="button"
                                        @click="$dispatch('confirm-delete', { formId: 'form-del-tpl-{{ $template->uuid }}', itemName: '{{ addslashes($template->name) }}' })"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-red-500 hover:text-red-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-red-400 rounded cursor-pointer"
                                        aria-label="Delete {{ $template->name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                    Delete
                                </button>
                                @endcan

                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-gray-500">No templates yet</p>
                            <p class="mt-1 text-xs text-gray-400">Create your first reusable message template to get started.</p>
                            @can('crm.communication.templates.manage')
                            <a href="{{ route('crm.communication.templates.create') }}" class="btn-primary-sm mt-5">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                New Template
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($templates->hasPages())
            <div class="border-t border-gray-100 px-6 py-3">
                {{ $templates->links() }}
            </div>
            @endif
        </div>

    </div>

    <x-crm.confirm-modal variant="delete" subtext="The template will be permanently removed and cannot be recovered." />
</x-layouts.crm>
