<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling;

use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Events\CRM\Counselling\WalkInTokenCalled;
use App\Events\CRM\Counselling\WalkInTokenStatusChanged;
use App\Models\CRM\Campus;
use App\Models\CRM\Lead;
use App\Models\CRM\WalkInToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// BRD: CRM-EC-019 — Token-based walk-in queue management: issue, call next, serve, skip, stats
final class WalkInQueueService
{
    /**
     * Issue a new walk-in token for the campus.
     * Optionally creates a Lead stub when visitor provides name and mobile.
     *
     * @param array{visitor_name?: string, visitor_mobile?: string, programme_interest?: string} $visitorData
     */
    public function issueToken(Campus $campus, array $visitorData): WalkInToken
    {
        return DB::transaction(function () use ($campus, $visitorData): WalkInToken {
            $tokenNumber = WalkInToken::nextTokenNumber((int) $campus->id);

            $leadId = null;
            if (! empty($visitorData['visitor_name']) && ! empty($visitorData['visitor_mobile'])) {
                $lead = Lead::withoutGlobalScopes()->create([
                    'institution_id' => $campus->institution_id,
                    'campus_id' => $campus->id,
                    'first_name' => $visitorData['visitor_name'],
                    'mobile' => $visitorData['visitor_mobile'],
                    'source' => LeadSource::WALK_IN->value,
                    'status' => LeadStatus::NEW_ENQUIRY->value,
                    'consent_given' => false,
                ]);
                $leadId = $lead->id;
            }

            $token = WalkInToken::withoutGlobalScopes()->create([
                'institution_id' => $campus->institution_id,
                'campus_id' => $campus->id,
                'token_number' => $tokenNumber,
                'token_date' => Carbon::today()->toDateString(),
                'lead_id' => $leadId,
                'visitor_name' => $visitorData['visitor_name'] ?? null,
                'visitor_mobile' => $visitorData['visitor_mobile'] ?? null,
                'programme_interest' => $visitorData['programme_interest'] ?? null,
                'status' => WalkInTokenStatus::WAITING->value,
            ]);

            WalkInTokenStatusChanged::dispatch($token);

            return $token;
        });
    }

    /**
     * Call the next waiting token at the campus.
     * Returns the token that was called.
     *
     * @throws \RuntimeException if there are no waiting tokens
     */
    public function callNext(Campus $campus, User $counsellor): WalkInToken
    {
        return DB::transaction(function () use ($campus, $counsellor): WalkInToken {
            $token = WalkInToken::withoutGlobalScopes()
                ->where('campus_id', $campus->id)
                ->whereDate('token_date', Carbon::today())
                ->where('status', WalkInTokenStatus::WAITING->value)
                ->orderBy('token_number')
                ->lockForUpdate()
                ->first();

            if ($token === null) {
                throw new \RuntimeException('No waiting tokens in the queue for this campus.');
            }

            $token->update([
                'status' => WalkInTokenStatus::CALLED->value,
                'counsellor_id' => $counsellor->id,
                'called_at' => now(),
            ]);

            WalkInTokenCalled::dispatch($token->fresh());

            return $token;
        });
    }

    /**
     * Mark a token as serving (counsellor has started the session).
     *
     * @throws \DomainException when the token is already in a terminal state
     */
    public function serve(WalkInToken $token): void
    {
        if ($token->status->isTerminal()) {
            throw new \DomainException("Token #{$token->token_number} is already {$token->status->label()}.");
        }

        $token->update([
            'status' => WalkInTokenStatus::SERVED->value,
            'served_at' => now(),
        ]);

        WalkInTokenStatusChanged::dispatch($token->fresh());
    }

    /**
     * Mark a token as skipped (visitor did not respond when called).
     *
     * @throws \DomainException when the token is already in a terminal state
     */
    public function skip(WalkInToken $token): void
    {
        if ($token->status->isTerminal()) {
            throw new \DomainException("Token #{$token->token_number} is already {$token->status->label()}.");
        }

        $token->update([
            'status' => WalkInTokenStatus::SKIPPED->value,
            'skipped_at' => now(),
        ]);

        WalkInTokenStatusChanged::dispatch($token->fresh());
    }

    /**
     * Returns daily analytics for today's tokens at the given campus.
     *
     * @return array{total: int, served: int, skipped: int, waiting: int, avg_wait_minutes: float|null}
     */
    public function dailyStats(Campus $campus): array
    {
        $tokens = WalkInToken::withoutGlobalScopes()
            ->where('campus_id', $campus->id)
            ->whereDate('token_date', Carbon::today())
            ->get();

        $total   = $tokens->count();
        $served  = $tokens->where('status', WalkInTokenStatus::SERVED)->count();
        $skipped = $tokens->where('status', WalkInTokenStatus::SKIPPED)->count();
        $waiting = $tokens->where('status', WalkInTokenStatus::WAITING)->count();

        $avgWait = $tokens
            ->whereNotNull('called_at')
            ->map(fn (WalkInToken $t) => $t->called_at->diffInMinutes($t->created_at))
            ->avg();

        return [
            'total' => $total,
            'served' => $served,
            'skipped' => $skipped,
            'waiting' => $waiting,
            'avg_wait_minutes' => $avgWait !== null ? round((float) $avgWait, 1) : null,
        ];
    }
}
