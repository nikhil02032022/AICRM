<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SA-004 — Full audit trail for all CRM data changes (read-only)
final class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('crm.admin.audit-logs.view');

        $logs = AuditLog::withoutGlobalScopes()
            ->where('institution_id', $request->user()->institution_id)
            ->when($request->input('entity'), fn ($q, $v) => $q->where('entity_type', 'like', '%'.$v.'%'))
            ->when($request->input('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->input('from'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->input('to'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->input('action'), fn ($q, $v) => $q->where('action', $v))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('crm.admin.audit-logs.index', compact('logs'));
    }

    public function show(int $id, Request $request): View
    {
        $this->authorize('crm.admin.audit-logs.view');

        $log = AuditLog::withoutGlobalScopes()
            ->where('institution_id', $request->user()->institution_id)
            ->findOrFail($id);

        return view('crm.admin.audit-logs.show', compact('log'));
    }
}
