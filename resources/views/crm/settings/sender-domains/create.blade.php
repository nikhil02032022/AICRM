<x-layouts.crm title="Add Sender Domain">
    <div class="max-w-xl space-y-6">

        <h1 class="text-2xl font-bold text-gray-900">Add Sender Domain</h1>

        <div class="card card-body bg-blue-50 text-sm text-blue-800 space-y-1">
            <p class="font-semibold">How domain verification works:</p>
            <ol class="list-decimal ml-4 space-y-1">
                <li>Enter your domain (e.g. <code>admissions.youruni.edu.in</code>)</li>
                <li>We will generate SPF, DKIM, and DMARC DNS records</li>
                <li>Add the records to your domain's DNS provider</li>
                <li>Click "Re-check" to verify propagation</li>
            </ol>
        </div>

        @if ($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        <form method="POST" action="{{ route('crm.settings.sender-domains.store') }}" class="card card-body space-y-5">
            @csrf

            {{-- Domain --}}
            <div>
                <label for="domain" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Domain Name <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="domain" name="domain" value="{{ old('domain') }}"
                    required placeholder="admissions.youruni.edu.in" maxlength="253"
                    pattern="^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('domain') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-400">Subdomain or root domain. Must match your sending email's @domain.</p>
                @error('domain')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Default From Name --}}
            <div>
                <label for="default_from_name" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Default "From" Name <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="default_from_name" name="default_from_name"
                    value="{{ old('default_from_name') }}" required maxlength="100"
                    placeholder="e.g. GIM Admissions"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('default_from_name') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-400">Displayed in recipients' inboxes as the sender name.</p>
                @error('default_from_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Default From Email --}}
            <div>
                <label for="default_from_email" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Default "From" Email <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="email" id="default_from_email" name="default_from_email"
                    value="{{ old('default_from_email') }}" required maxlength="255"
                    placeholder="admissions@youruni.edu.in"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('default_from_email') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-400">Must use the domain above as its @domain part.</p>
                @error('default_from_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Email Provider --}}
            <div>
                <label for="provider" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Email Provider <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select id="provider" name="provider" required
                    class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('provider') border-red-500 @enderror">
                    <option value="">Select provider…</option>
                    @foreach (\App\Enums\CRM\EmailProvider::cases() as $ep)
                        <option value="{{ $ep->value }}" @selected(old('provider') === $ep->value)>{{ $ep->label() }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400">The email delivery service you have configured for this domain.</p>
                @error('provider')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Set as Default --}}
            <div class="flex items-center gap-3">
                <input type="checkbox" id="is_default" name="is_default" value="1"
                    @checked(old('is_default'))
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <label for="is_default" class="text-sm text-gray-700">Set as default sender domain for this institution</label>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" class="btn-primary">Add &amp; Generate DNS Records</button>
                <a href="{{ route('crm.settings.sender-domains.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>

    </div>
</x-layouts.crm>
