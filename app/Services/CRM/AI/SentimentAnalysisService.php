<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\SentimentLabel;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\Lead;
use App\Models\CRM\SentimentFlag;
use Illuminate\Support\Str;

// BRD: CRM-AI-004 — Heuristic inbound sentiment analysis for communication triage
final class SentimentAnalysisService
{
    public function analyzeAndPersist(Lead $lead): SentimentFlag
    {
        $latestInbound = CommunicationLog::query()
            ->where('lead_id', $lead->id)
            ->where('direction', MessageDirection::INBOUND->value)
            ->latest('created_at')
            ->first();

        $text = Str::lower((string) ($latestInbound?->body_preview ?? $latestInbound?->subject ?? ''));

        $negativeKeywords = ['not interested', 'stop', 'unsubscribe', 'bad', 'angry', 'complaint', 'poor', 'frustrated'];
        $urgentKeywords = ['urgent', 'immediately', 'asap', 'today', 'now', 'deadline'];
        $positiveKeywords = ['thanks', 'thank you', 'interested', 'excited', 'great', 'good'];

        $negativeHits = $this->countHits($text, $negativeKeywords);
        $urgentHits = $this->countHits($text, $urgentKeywords);
        $positiveHits = $this->countHits($text, $positiveKeywords);

        $sentimentScore = max(-100, min(100, (($positiveHits * 18) - ($negativeHits * 25) - ($urgentHits * 10))));

        $label = match (true) {
            $sentimentScore <= -20 => SentimentLabel::NEGATIVE,
            $sentimentScore >= 20 => SentimentLabel::POSITIVE,
            default => SentimentLabel::NEUTRAL,
        };

        $isUrgent = $urgentHits > 0 || str_contains($text, 'call me');

        $rationale = sprintf(
            'Sentiment evaluated from latest inbound communication: %d negative, %d urgent, %d positive signals.',
            $negativeHits,
            $urgentHits,
            $positiveHits,
        );

        return SentimentFlag::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'lead_id' => $lead->id,
            'channel' => $latestInbound?->channel?->value,
            'sentiment_label' => $label,
            'sentiment_score' => $sentimentScore,
            'is_urgent' => $isUrgent,
            'rationale' => $rationale,
            'source_excerpt' => Str::limit((string) ($latestInbound?->body_preview ?? ''), 500, ''),
            'indicators' => [
                'negative_hits' => $negativeHits,
                'urgent_hits' => $urgentHits,
                'positive_hits' => $positiveHits,
            ],
            'model_version' => 'a2a-sentiment-rules-v1',
            'flagged_at' => now(),
        ]);
    }

    private function countHits(string $text, array $keywords): int
    {
        $hits = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $hits++;
            }
        }

        return $hits;
    }
}
