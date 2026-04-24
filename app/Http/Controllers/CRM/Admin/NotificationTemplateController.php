<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\Admin\NotificationTemplate;
use App\Services\CRM\Admin\NotificationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SA-009 — Email and notification template management
final class NotificationTemplateController extends Controller
{
    public function __construct(private readonly NotificationTemplateService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.admin.notification-templates.manage');

        $templates = NotificationTemplate::when(
            $request->input('channel'),
            fn ($q, $v) => $q->where('channel', $v)
        )->orderBy('name')->paginate(25)->withQueryString();

        return view('crm.admin.notification-templates.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('crm.admin.notification-templates.manage');

        return view('crm.admin.notification-templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('crm.admin.notification-templates.manage');

        $validated = $request->validate([
            'channel'   => 'required|in:email,sms,whatsapp',
            'name'      => 'required|string|max:150',
            'subject'   => 'nullable|string|max:255',
            'body'      => 'required|string',
            'is_active' => 'boolean',
        ]);

        $validated['institution_id'] = $request->user()->institution_id;

        $this->service->create($validated);

        return redirect()->route('crm.admin.notification-templates.index')
            ->with('success', 'Notification template created.');
    }

    public function edit(NotificationTemplate $notificationTemplate): View
    {
        $this->authorize('crm.admin.notification-templates.manage');

        return view('crm.admin.notification-templates.edit', ['template' => $notificationTemplate]);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $this->authorize('crm.admin.notification-templates.manage');

        $validated = $request->validate([
            'channel'   => 'required|in:email,sms,whatsapp',
            'name'      => 'required|string|max:150',
            'subject'   => 'nullable|string|max:255',
            'body'      => 'required|string',
            'is_active' => 'boolean',
        ]);

        $this->service->update($notificationTemplate, $validated);

        return redirect()->route('crm.admin.notification-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $this->authorize('crm.admin.notification-templates.manage');

        $this->service->delete($notificationTemplate);

        return redirect()->route('crm.admin.notification-templates.index')
            ->with('success', 'Template deleted.');
    }

    public function preview(Request $request, NotificationTemplate $notificationTemplate): JsonResponse
    {
        $this->authorize('crm.admin.notification-templates.manage');

        $body = $notificationTemplate->body;

        // Replace all merge tags with placeholder values for preview
        $body = preg_replace('/\{\{([^}]+)\}\}/', '<span class="bg-yellow-100 px-1 rounded">[$1]</span>', $body);

        return response()->json(['preview' => $body]);
    }
}
