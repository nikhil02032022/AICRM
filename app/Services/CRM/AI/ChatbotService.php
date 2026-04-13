<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\ChatLead;
use App\Repositories\CRM\Marketing\ChatLeadRepositoryInterface;

// BRD: CRM-AI-006 — Conversational AI reply generation for web chatbot with escalation intent detection
final class ChatbotService
{
    public function __construct(
        private readonly ChatLeadRepositoryInterface $chatLeadRepository,
    ) {}

    /** @return array{reply: string, escalate: bool, escalation_reason: string|null, intent: string} */
    public function generateReply(ChatLead $chatLead): array
    {
        $transcript = is_array($chatLead->transcript) ? $chatLead->transcript : [];
        $latestUserMessage = $this->latestUserMessage($transcript);

        if ($latestUserMessage === null) {
            return [
                'reply' => 'Hello! I can help with programme details, eligibility, fees, brochure requests, and booking a counsellor call. What would you like to know?',
                'escalate' => false,
                'escalation_reason' => null,
                'intent' => 'welcome',
            ];
        }

        $intent = $this->detectIntent($latestUserMessage);
        $escalate = $this->shouldEscalate($latestUserMessage, $intent);
        $escalationReason = $escalate ? 'lead_requested_human_agent' : null;

        return [
            'reply' => $this->buildReply($intent, $escalate),
            'escalate' => $escalate,
            'escalation_reason' => $escalationReason,
            'intent' => $intent,
        ];
    }

    /** @param array{reply: string, escalate: bool, escalation_reason: string|null, intent: string} $aiOutput */
    public function applyReply(ChatLead $chatLead, array $aiOutput): ChatLead
    {
        $updated = $this->chatLeadRepository->appendTranscriptMessage(
            $chatLead,
            'assistant',
            $aiOutput['reply'],
        );

        $metadata = is_array($updated->metadata) ? $updated->metadata : [];
        $metadata['ai_reply'] = [
            'intent' => $aiOutput['intent'],
            'escalated' => $aiOutput['escalate'],
            'escalation_reason' => $aiOutput['escalation_reason'],
            'generated_at' => now()->toIso8601String(),
        ];

        $payload = ['metadata' => $metadata];

        if ($aiOutput['escalate']) {
            $payload['handoff_status'] = 'pending_agent';
        }

        return $this->chatLeadRepository->update($updated, $payload);
    }

    /** @param array<int, mixed> $transcript */
    private function latestUserMessage(array $transcript): ?string
    {
        $latest = collect($transcript)
            ->reverse()
            ->first(static fn (mixed $entry): bool => is_array($entry)
                && (($entry['role'] ?? null) === 'user')
                && is_string($entry['content'] ?? null)
                && trim((string) $entry['content']) !== '');

        if (!is_array($latest)) {
            return null;
        }

        return trim((string) ($latest['content'] ?? ''));
    }

    private function detectIntent(string $message): string
    {
        $normalized = mb_strtolower($message);

        if ($this->containsAny($normalized, ['brochure', 'prospectus', 'pdf'])) {
            return 'brochure_request';
        }

        if ($this->containsAny($normalized, ['book', 'appointment', 'schedule', 'call back', 'callback', 'counsellor call'])) {
            return 'appointment_booking';
        }

        if ($this->containsAny($normalized, ['fee', 'fees', 'tuition', 'scholarship', 'payment'])) {
            return 'faq_fees';
        }

        if ($this->containsAny($normalized, ['eligibility', 'criteria', 'required marks', 'minimum marks', 'cutoff'])) {
            return 'faq_eligibility';
        }

        if ($this->containsAny($normalized, ['agent', 'human', 'counsellor', 'representative', 'speak to someone'])) {
            return 'handoff_request';
        }

        return 'general_faq';
    }

    private function shouldEscalate(string $message, string $intent): bool
    {
        if ($intent === 'handoff_request') {
            return true;
        }

        $normalized = mb_strtolower($message);

        return $this->containsAny($normalized, [
            'complaint',
            'angry',
            'frustrated',
            'urgent',
            'not happy',
            'worst',
            'problem',
        ]);
    }

    private function buildReply(string $intent, bool $escalate): string
    {
        if ($escalate) {
            return 'I am connecting you with a counsellor right away. A team member will continue this chat shortly.';
        }

        return match ($intent) {
            'brochure_request' => 'Sure, I can help with that. Please share your programme of interest and I will have our admissions brochure sent to your registered email/WhatsApp.',
            'appointment_booking' => 'Great. Please share your preferred date and time slot, and I will arrange a counsellor callback for you.',
            'faq_fees' => 'Fee structures vary by programme and campus. Share your programme preference, and I will provide the latest fee and scholarship details.',
            'faq_eligibility' => 'Eligibility depends on the programme and admission cycle. Tell me which course you are applying for, and I will share the exact criteria.',
            default => 'I can help with programme details, fees, eligibility, brochure requests, and booking a counsellor call. What would you like to do next?',
        };
    }

    /** @param list<string> $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
