<x-layouts.crm title="API Token Management">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">API Token Management</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Issue and revoke API tokens for Power BI, Tableau, or any BI tool.
                    All tokens are institution-scoped and carry the <code class="text-xs bg-gray-100 rounded px-1">analytics:read</code> ability.
                </p>
            </div>
            <a href="{{ route('crm.admin.system-config.index') }}" class="btn-secondary text-sm">
                &larr; System Config
            </a>
        </div>

        {{-- One-time plain-text token reveal --}}
        @if(session('plain_token'))
            <div class="rounded-lg border border-yellow-300 bg-yellow-50 px-5 py-4 space-y-2">
                <p class="text-sm font-semibold text-yellow-800">Token issued — copy it now. It will not be shown again.</p>
                <div class="flex items-center gap-3">
                    <code class="flex-1 break-all rounded bg-white border border-yellow-200 px-3 py-2 text-sm font-mono text-yellow-900">{{ session('plain_token') }}</code>
                    <button
                        type="button"
                        onclick="navigator.clipboard.writeText('{{ session('plain_token') }}')"
                        class="shrink-0 btn-secondary text-sm"
                    >Copy</button>
                </div>
            </div>
        @endif

        {{-- Success / error flash --}}
        @if(session('success') && !session('plain_token'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Issue new token form --}}
            <div class="lg:col-span-1">
                <div class="card p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Issue New Token</h2>
                    <form method="POST" action="{{ route('crm.admin.api-tokens.store') }}" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="token-name">Token Name</label>
                            <input
                                id="token-name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                maxlength="100"
                                required
                                placeholder="e.g. PowerBI Production"
                                class="form-input"
                            >
                            <p class="mt-1 text-xs text-gray-400">Descriptive label (max 100 chars).</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            Token will be granted the <code class="bg-gray-100 rounded px-1">analytics:read</code> scope and
                            bound to your institution. Rate limit: 60 requests/minute.
                        </p>
                        <button type="submit" class="btn-primary w-full">Issue Token</button>
                    </form>
                </div>
            </div>

            {{-- Active tokens list --}}
            <div class="lg:col-span-2">
                <div class="card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">Active Tokens</h2>
                    </div>

                    @if($tokens->isEmpty())
                        <div class="px-6 py-10 text-center text-sm text-gray-400">
                            No API tokens issued yet.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Name</th>
                                        <th class="px-4 py-3 text-left">Ability</th>
                                        <th class="px-4 py-3 text-left">Created</th>
                                        <th class="px-4 py-3 text-left">Last Used</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($tokens as $token)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $token->name }}</td>
                                            <td class="px-4 py-3">
                                                @foreach(json_decode($token->abilities, true) ?? [] as $ability)
                                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $ability }}</span>
                                                @endforeach
                                            </td>
                                            <td class="px-4 py-3 text-gray-500">{{ $token->created_at->format('d M Y') }}</td>
                                            <td class="px-4 py-3 text-gray-500">
                                                {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <form
                                                    method="POST"
                                                    action="{{ route('crm.admin.api-tokens.destroy', $token) }}"
                                                    onsubmit="return confirm('Revoke token \'{{ addslashes($token->name) }}\'? All BI tools using this token will lose access immediately.')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Revoke</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700 space-y-1">
                    <p class="font-semibold">Power BI / Tableau setup:</p>
                    <p>Use Bearer token authentication. Base URL: <code class="bg-white rounded px-1">{{ url('/api/v1/crm/analytics') }}</code></p>
                    <p>Available endpoints: <code class="bg-white rounded px-1">/leads</code> &nbsp; <code class="bg-white rounded px-1">/pipeline</code> &nbsp; <code class="bg-white rounded px-1">/fees</code> &nbsp; <code class="bg-white rounded px-1">/counsellors</code></p>
                    <p>All responses are aggregate only — no personal data is returned.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.crm>
