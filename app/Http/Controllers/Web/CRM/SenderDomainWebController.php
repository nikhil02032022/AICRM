<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreSenderDomainRequest;
use App\Models\CRM\SenderDomain;
use App\Services\CRM\Communication\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// BRD: CRM-CC-004 — Sender domain verification (web)
final class SenderDomainWebController extends Controller
{
    public function __construct(
        private readonly EmailService $emailService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.settings.manage');

        $domains = SenderDomain::orderByDesc('created_at')->paginate(20);

        return view('crm.settings.sender-domains.index', compact('domains'));
    }

    public function create(): View
    {
        $this->authorize('crm.settings.manage');

        return view('crm.settings.sender-domains.create');
    }

    public function store(StoreSenderDomainRequest $request): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $domain = SenderDomain::create([
            ...$request->validated(),
            'institution_id' => $request->user()->institution_id,
        ]);

        $this->emailService->verifySenderDomain($domain);

        return redirect()
            ->route('crm.settings.sender-domains.show', $domain->uuid)
            ->with('success', 'Domain added. Please add the DNS records below to complete verification.');
    }

    public function show(SenderDomain $senderDomain): View
    {
        $this->authorize('crm.settings.manage');

        return view('crm.settings.sender-domains.show', compact('senderDomain'));
    }

    public function checkDns(SenderDomain $senderDomain): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $this->emailService->verifySenderDomain($senderDomain);

        $senderDomain->refresh();

        if ($senderDomain->isFullyVerified()) {
            return back()->with('success', 'Domain fully verified! You can now send from this domain.');
        }

        return back()->with('warning', 'Some DNS records are still pending. Please wait a few minutes and try again.');
    }

    public function destroy(SenderDomain $senderDomain): RedirectResponse
    {
        $this->authorize('crm.settings.manage');

        $senderDomain->delete();

        return redirect()
            ->route('crm.settings.sender-domains.index')
            ->with('success', 'Sender domain removed.');
    }
}
