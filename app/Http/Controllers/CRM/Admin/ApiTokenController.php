<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Policies\CRM\Admin\ApiTokenPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

// BRD: CRM-AR-021 — Token management UI for institution-scoped BI API keys
final class ApiTokenController extends Controller
{
    /** GET crm/admin/api-tokens */
    public function index(Request $request): View
    {
        $this->authorize('manage', PersonalAccessToken::class);

        $tokens = PersonalAccessToken::query()
            ->where('tokenable_type', get_class($request->user()))
            ->whereIn(
                'tokenable_id',
                \App\Models\User::query()
                    ->where('institution_id', $request->user()->institution_id)
                    ->pluck('id')
            )
            ->where('institution_id', $request->user()->institution_id)
            ->latest()
            ->get();

        return view('crm.admin.api-tokens.index', compact('tokens'));
    }

    /** POST crm/admin/api-tokens */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', PersonalAccessToken::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $newToken = $request->user()->createToken($validated['name'], ['analytics:read']);

        // Bind institution_id to the token record for scoped API enforcement
        DB::table('personal_access_tokens')
            ->where('id', $newToken->accessToken->id)
            ->update(['institution_id' => $request->user()->institution_id]);

        return redirect()
            ->route('crm.admin.api-tokens.index')
            ->with('plain_token', $newToken->plainTextToken)
            ->with('success', 'API token issued. Copy it now — it will not be shown again.');
    }

    /** DELETE crm/admin/api-tokens/{token} */
    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        $this->authorize('manage', PersonalAccessToken::class);

        // Cross-institution guard: institution admins may only revoke their own tokens
        if ($token->institution_id !== $request->user()->institution_id) {
            abort(403);
        }

        $token->delete();

        return redirect()
            ->route('crm.admin.api-tokens.index')
            ->with('success', 'API token revoked.');
    }
}
