<x-layouts.crm :title="'DNS Records: ' . $senderDomain->domain">
    <div class="max-w-3xl space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">DNS Records for <span class="font-mono">{{ $senderDomain->domain }}</span></h1>
            <form method="POST" action="{{ route('crm.settings.sender-domains.check-dns', $senderDomain->uuid) }}">
                @csrf
                <button type="submit" class="btn-secondary">Re-check DNS</button>
            </form>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif
        @if (session('warning'))
            <x-alert type="warning" :message="session('warning')" />
        @endif

        <div class="space-y-4">
            @foreach ([
                ['label' => 'SPF', 'type' => 'TXT', 'host' => $senderDomain->domain, 'value' => $senderDomain->spf_record, 'verified' => $senderDomain->spf_verified],
                ['label' => 'DKIM', 'type' => 'TXT', 'host' => 'crm._domainkey.' . $senderDomain->domain, 'value' => $senderDomain->dkim_record, 'verified' => $senderDomain->dkim_verified],
                ['label' => 'DMARC', 'type' => 'TXT', 'host' => '_dmarc.' . $senderDomain->domain, 'value' => $senderDomain->dmarc_record, 'verified' => $senderDomain->dmarc_verified],
            ] as $rec)
            <div class="card card-body space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-800">{{ $rec['label'] }}</span>
                    <span @class(['badge', 'badge-green' => $rec['verified'], 'badge-yellow' => !$rec['verified']])>
                        {{ $rec['verified'] ? 'Verified' : 'Pending' }}
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div><span class="text-gray-400 block">Type</span><span class="font-mono">{{ $rec['type'] }}</span></div>
                    <div><span class="text-gray-400 block">Host / Name</span><span class="font-mono break-all">{{ $rec['host'] }}</span></div>
                    <div><span class="text-gray-400 block">Value</span><span class="font-mono break-all">{{ $rec['value'] ?? '—' }}</span></div>
                </div>
            </div>
            @endforeach
        </div>

        <a href="{{ route('crm.settings.sender-domains.index') }}" class="btn-secondary inline-flex">← Back to domains</a>

    </div>
</x-layouts.crm>
