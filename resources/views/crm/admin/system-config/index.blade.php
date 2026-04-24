<x-layouts.crm title="System Configuration">
    <div
        class="space-y-6"
        x-data="{
            activeTab: 'general',
            tabs: [
                { id: 'general',       label: 'General' },
                { id: 'branding',      label: 'Branding' },
                { id: 'business_hours',label: 'Business Hours' },
                { id: 'locale',        label: 'Locale' },
                { id: 'notifications', label: 'Notifications' },
                { id: 'api_tokens',    label: 'API Tokens' },
                { id: 'ip_whitelist',  label: 'IP Whitelist' },
            ]
        }"
    >

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">System Configuration</h1>
                <p class="mt-1 text-sm text-gray-500">Global settings for your CRM instance</p>
            </div>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Tab navigation --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Config tabs">
                <template x-for="tab in tabs" :key="tab.id">
                    <button
                        type="button"
                        @click="activeTab = tab.id"
                        :aria-selected="activeTab === tab.id"
                        :class="activeTab === tab.id
                            ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold'
                            : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap px-4 py-2.5 text-sm transition-colors"
                        x-text="tab.label"
                    ></button>
                </template>
            </nav>
        </div>

        {{-- ── GENERAL ── --}}
        <div x-show="activeTab === 'general'" x-cloak>
            <form method="POST" action="{{ route('crm.admin.system-config.update') }}">
                @csrf
                <input type="hidden" name="tab" value="general">
                <div class="card p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">General Settings</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="form-group">
                            <label class="form-label">Application Name</label>
                            <input type="text" name="config[app_name]" value="{{ old('config.app_name', $config['app_name'] ?? '') }}" class="form-input" placeholder="My CRM">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Support Email</label>
                            <input type="email" name="config[support_email]" value="{{ old('config.support_email', $config['support_email'] ?? '') }}" class="form-input" placeholder="support@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Items Per Page</label>
                            <input type="number" name="config[per_page]" value="{{ old('config.per_page', $config['per_page'] ?? 25) }}" min="5" max="200" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" name="config[session_timeout]" value="{{ old('config.session_timeout', $config['session_timeout'] ?? 120) }}" min="15" class="form-input">
                        </div>
                        <div class="form-group sm:col-span-2">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="config[maintenance_mode]" value="0">
                                <input
                                    type="checkbox"
                                    name="config[maintenance_mode]"
                                    value="1"
                                    {{ ($config['maintenance_mode'] ?? false) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span class="form-label mb-0">Maintenance Mode</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-400">When enabled, only administrators can access the system.</p>
                        </div>
                    </div>

                    <div class="flex border-t border-gray-100 pt-5">
                        <button type="submit" class="btn-primary">Save General Settings</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── BRANDING ── --}}
        <div x-show="activeTab === 'branding'" x-cloak>
            <form method="POST" action="{{ route('crm.admin.system-config.update') }}">
                @csrf
                <input type="hidden" name="tab" value="branding">
                <div class="card p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Branding</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="form-group">
                            <label class="form-label">Primary Colour</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="config[primary_color]" value="{{ old('config.primary_color', $config['primary_color'] ?? '#6366f1') }}" class="h-10 w-16 cursor-pointer rounded-lg border border-gray-200 p-1">
                                <span class="text-xs text-gray-500">Brand accent colour</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Logo URL</label>
                            <input type="url" name="config[logo_url]" value="{{ old('config.logo_url', $config['logo_url'] ?? '') }}" class="form-input" placeholder="https://…">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Favicon URL</label>
                            <input type="url" name="config[favicon_url]" value="{{ old('config.favicon_url', $config['favicon_url'] ?? '') }}" class="form-input" placeholder="https://…">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Application Tagline</label>
                            <input type="text" name="config[tagline]" value="{{ old('config.tagline', $config['tagline'] ?? '') }}" class="form-input" placeholder="Powering student success">
                        </div>
                    </div>

                    <div class="flex border-t border-gray-100 pt-5">
                        <button type="submit" class="btn-primary">Save Branding</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── BUSINESS HOURS ── --}}
        <div x-show="activeTab === 'business_hours'" x-cloak>
            <form method="POST" action="{{ route('crm.admin.system-config.update') }}">
                @csrf
                <input type="hidden" name="tab" value="business_hours">
                <div class="card p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Business Hours</h2>
                    <p class="text-xs text-gray-500">Define the hours during which automated actions and SLA timers run.</p>

                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                        @php $key = strtolower($day); @endphp
                        <div class="flex items-center gap-4 py-2 border-b border-gray-50 last:border-0">
                            <div class="w-28 text-sm font-medium text-gray-700">{{ $day }}</div>
                            <label class="flex items-center gap-2 cursor-pointer min-w-[70px]">
                                <input
                                    type="checkbox"
                                    name="config[hours][{{ $key }}][enabled]"
                                    value="1"
                                    {{ ($config['hours'][$key]['enabled'] ?? ($key !== 'saturday' && $key !== 'sunday')) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span class="text-xs text-gray-500">Open</span>
                            </label>
                            <div class="flex items-center gap-2 text-sm">
                                <input type="time" name="config[hours][{{ $key }}][open]" value="{{ $config['hours'][$key]['open'] ?? '08:00' }}" class="form-input py-1.5 text-sm w-32">
                                <span class="text-gray-400">to</span>
                                <input type="time" name="config[hours][{{ $key }}][close]" value="{{ $config['hours'][$key]['close'] ?? '17:00' }}" class="form-input py-1.5 text-sm w-32">
                            </div>
                        </div>
                    @endforeach

                    <div class="flex border-t border-gray-100 pt-5">
                        <button type="submit" class="btn-primary">Save Business Hours</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── LOCALE ── --}}
        <div x-show="activeTab === 'locale'" x-cloak>
            <form method="POST" action="{{ route('crm.admin.system-config.update') }}">
                @csrf
                <input type="hidden" name="tab" value="locale">
                <div class="card p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Locale &amp; Regionalisation</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="form-group">
                            <label class="form-label">Default Language</label>
                            <select name="config[locale]" class="form-input">
                                @foreach(['en' => 'English', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'en_AU' => 'English (AU)', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish', 'ar' => 'Arabic', 'zh' => 'Chinese', 'hi' => 'Hindi'] as $code => $label)
                                    <option value="{{ $code }}" @selected(($config['locale'] ?? 'en') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default Timezone</label>
                            <select name="config[timezone]" class="form-input">
                                @foreach(\DateTimeZone::listIdentifiers() as $tz)
                                    <option value="{{ $tz }}" @selected(($config['timezone'] ?? 'UTC') === $tz)>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date Format</label>
                            <select name="config[date_format]" class="form-input">
                                @foreach(['d/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'd M Y' => 'DD Mon YYYY'] as $fmt => $lbl)
                                    <option value="{{ $fmt }}" @selected(($config['date_format'] ?? 'd/m/Y') === $fmt)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Currency</label>
                            <select name="config[currency]" class="form-input">
                                @foreach(['AUD' => 'Australian Dollar (AUD)', 'USD' => 'US Dollar (USD)', 'GBP' => 'British Pound (GBP)', 'EUR' => 'Euro (EUR)', 'INR' => 'Indian Rupee (INR)', 'CAD' => 'Canadian Dollar (CAD)'] as $code => $label)
                                    <option value="{{ $code }}" @selected(($config['currency'] ?? 'AUD') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex border-t border-gray-100 pt-5">
                        <button type="submit" class="btn-primary">Save Locale Settings</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── NOTIFICATIONS ── --}}
        <div x-show="activeTab === 'notifications'" x-cloak>
            <form method="POST" action="{{ route('crm.admin.system-config.update') }}">
                @csrf
                <input type="hidden" name="tab" value="notifications">
                <div class="card p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Notification Settings</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="form-group">
                            <label class="form-label">Default From Email</label>
                            <input type="email" name="config[mail_from_address]" value="{{ old('config.mail_from_address', $config['mail_from_address'] ?? '') }}" class="form-input" placeholder="no-reply@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default From Name</label>
                            <input type="text" name="config[mail_from_name]" value="{{ old('config.mail_from_name', $config['mail_from_name'] ?? '') }}" class="form-input" placeholder="CRM System">
                        </div>
                    </div>

                    @foreach([
                        'email_notifications_enabled'    => 'Enable Email Notifications',
                        'sms_notifications_enabled'      => 'Enable SMS Notifications',
                        'whatsapp_notifications_enabled' => 'Enable WhatsApp Notifications',
                        'in_app_notifications_enabled'   => 'Enable In-App Notifications',
                        'notify_on_lead_created'         => 'Notify on New Lead',
                        'notify_on_application_status'   => 'Notify on Application Status Change',
                        'notify_on_task_assigned'        => 'Notify on Task Assignment',
                        'notify_on_document_uploaded'    => 'Notify on Document Upload',
                    ] as $key => $label)
                        <div class="form-group">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="config[{{ $key }}]" value="0">
                                <input
                                    type="checkbox"
                                    name="config[{{ $key }}]"
                                    value="1"
                                    {{ ($config[$key] ?? true) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span class="form-label mb-0">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach

                    <div class="flex border-t border-gray-100 pt-5">
                        <button type="submit" class="btn-primary">Save Notification Settings</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── API TOKENS ── --}}
        <div x-show="activeTab === 'api_tokens'" x-cloak>
            <div class="card p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Analytics API Tokens</h2>
                <p class="text-sm text-gray-600">
                    Issue institution-scoped Bearer tokens for Power BI, Tableau, or any BI tool. Tokens carry the
                    <code class="text-xs bg-gray-100 rounded px-1">analytics:read</code> scope and are rate-limited
                    to 60 requests per minute. Aggregate data only — no personal information is returned.
                </p>
                <div class="flex gap-3">
                    <a href="{{ route('crm.admin.api-tokens.index') }}" class="btn-primary">
                        Manage API Tokens
                    </a>
                </div>
                <div class="mt-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700 space-y-1">
                    <p class="font-semibold">Available endpoints</p>
                    <p>Base URL: <code class="bg-white rounded px-1">{{ url('/api/v1/crm/analytics') }}</code></p>
                    <p><code class="bg-white rounded px-1">GET /leads</code> — Lead funnel metrics by stage and source</p>
                    <p><code class="bg-white rounded px-1">GET /pipeline</code> — Application counts by programme</p>
                    <p><code class="bg-white rounded px-1">GET /fees</code> — Fee collection summary</p>
                    <p><code class="bg-white rounded px-1">GET /counsellors</code> — Counsellor performance metrics</p>
                </div>
            </div>
        </div>

        {{-- ── IP WHITELIST (NFR-SE-005) ── --}}
        <div x-show="activeTab === 'ip_whitelist'" x-cloak>
            <div class="card p-6 space-y-5">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Admin IP Whitelist</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Restrict admin panel access to specific IP addresses. Leave blank to allow access from any IP.
                        Add one IP address per line.
                    </p>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <strong>Warning:</strong> Ensure your own IP address is listed before saving, or you will be locked out.
                    Use <code class="bg-white rounded px-1">php artisan crm:admin:clear-ip-whitelist</code> for emergency recovery.
                </div>

                <form method="POST" action="{{ route('crm.admin.system-config.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="admin_ip_whitelist" class="form-label">Allowed IP Addresses</label>
                        <textarea
                            id="admin_ip_whitelist"
                            name="admin_ip_whitelist"
                            rows="8"
                            class="form-input font-mono text-sm"
                            placeholder="192.168.1.1&#10;10.0.0.1&#10;203.0.113.42"
                        >{{ old('admin_ip_whitelist', $config['admin_ip_whitelist'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">One IP address per line.</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">Save IP Whitelist</button>
                    </div>
                </form>

                @if(!empty($config['admin_ip_whitelist']))
                    <p class="text-sm font-medium text-green-700">IP whitelist is <strong>active</strong>. Admin access is restricted.</p>
                @else
                    <p class="text-sm text-gray-500">IP whitelist is <strong>inactive</strong>. Admin panel is accessible from any IP.</p>
                @endif
            </div>
        </div>

    </div>
</x-layouts.crm>
