<?php

namespace App\Services;

use App\CrmEmail;
use Illuminate\Support\Facades\Http;

/**
 * Shared ElevenLabs voice-call plumbing for the CRM (Voice Calls page + voice:process-queue).
 * Credentials live in .env and are synced by /opt/elvoice/el_migrate.py on account switches.
 */
class VoiceCallService
{
    public function cfg(): array
    {
        return [
            'key'   => (string) env('ELEVENLABS_API_KEY', ''),
            'agent' => (string) env('ELEVENLABS_AGENT_ID', ''),
            'phone' => (string) env('ELEVENLABS_PHONE_ID', ''),
        ];
    }

    public function configured(): bool
    {
        $c = $this->cfg();
        return $c['key'] && $c['agent'] && $c['phone'];
    }

    public function api()
    {
        return Http::withHeaders(['xi-api-key' => $this->cfg()['key']])
            ->baseUrl('https://api.elevenlabs.io/v1');
    }

    /** "03454428186" / "+92 345..." / "0092..." -> E.164, or null when it cannot be one. */
    public function normalizePhone(?string $input): ?string
    {
        $raw = preg_replace('/[^0-9+]/', '', (string) $input);
        if ($raw === '') {
            return null;
        }
        if (str_starts_with($raw, '+')) {
            $to = '+' . preg_replace('/\D/', '', substr($raw, 1));
        } elseif (str_starts_with($raw, '00')) {
            $to = '+' . substr($raw, 2);
        } elseif (str_starts_with($raw, '0') && strlen($raw) === 11) {
            $to = '+92' . substr($raw, 1); // Pakistani local format
        } else {
            $to = '+' . $raw;
        }
        return preg_match('/^\+[1-9]\d{9,14}$/', $to) ? $to : null;
    }

    /**
     * Follow-up context for an existing lead: Sara greets them by name about THEIR enquiry
     * and works the quote follow-up instead of re-qualifying from scratch.
     */
    public function leadOverride(CrmEmail $lead): array
    {
        $first = trim(explode(' ', trim((string) $lead->client_name))[0] ?: 'there');
        $product = trim((string) ($lead->product_name ?: 'packaging'));
        $size = ($lead->length && $lead->width && $lead->height)
            ? "{$lead->length}x{$lead->width}x{$lead->height} " . ($lead->unit ?: '')
            : null;
        $facts = array_filter([
            'Name: ' . ($lead->client_name ?: 'unknown'),
            'Product: ' . $product,
            $lead->quantity ? 'Quantity: ' . $lead->quantity : null,
            $size ? 'Size: ' . trim($size) : null,
            $lead->stock ? 'Material: ' . $lead->stock : null,
            $lead->color ? 'Printing: ' . $lead->color : null,
            $lead->coating ? 'Finish: ' . $lead->coating : null,
            'Lead status in CRM: ' . ($lead->status ?: 'New'),
            $lead->client_email ? 'Email on file: ' . $lead->client_email : null,
        ]);

        $prompt =
            'You are Sara, a professional female sales representative for The Premium Boxes, a direct manufacturer of custom printed packaging. ' .
            'This is a FOLLOW-UP phone call to an existing lead, not a first enquiry. If asked, you are an AI assistant. ' .
            'Lead details on file: ' . implode('; ', $facts) . '. ' .
            'How you speak: polished, warm but businesslike; one or two short sentences per turn and only ONE question at a time; ' .
            'no gushing words (lovely, wonderful, amazing, perfect, great), no exclamation marks, never output emotion tags like [happy]. ' .
            'Call goals, in order: (1) confirm you are speaking with ' . ($lead->client_name ?: 'the customer') . '; ' .
            '(2) say you are following up on their ' . $product . ' enquiry and ask if they received the quote email from our team - if not, ask them to check spam and confirm the email address on file; ' .
            '(3) answer questions using the lead details above; NEVER state any price, discount or price range - pricing always comes from the team by email; ' .
            '(4) if they want changes to the specs, note the changes clearly and say the team will email an updated quote; ' .
            '(5) if they are ready to proceed, say the team will email the next steps for artwork and production right away; ' .
            '(6) if they are not interested, politely ask the reason, note it, and thank them for their time. ' .
            'Do not re-qualify from scratch; only ask for a genuinely missing essential detail if the conversation needs it. ' .
            'When the conversation is finished or they say goodbye, give a brief professional goodbye and END THE CALL using the end_call tool. Never stay silent on the line.';

        $firstMessage = "Hello, may I speak with {$first}? This is Sara calling from The Premium Boxes, following up on your {$product} enquiry. Is this a good time to talk?";

        return [
            'conversation_config_override' => [
                'agent' => [
                    'first_message' => $firstMessage,
                    'prompt' => ['prompt' => $prompt],
                ],
            ],
        ];
    }

    /**
     * Place an outbound call. Blocks while dialing (up to ~90s).
     * Returns [bool $ok, string $conversationIdOrMessage].
     */
    public function placeCall(string $to, ?CrmEmail $lead = null): array
    {
        $cfg = $this->cfg();
        if (!$this->configured()) {
            return [false, 'ElevenLabs keys CRM .env mein set nahi hain.'];
        }
        $payload = [
            'agent_id' => $cfg['agent'],
            'agent_phone_number_id' => $cfg['phone'],
            'to_number' => $to,
        ];
        if ($lead) {
            $payload['conversation_initiation_client_data'] = $this->leadOverride($lead);
        }
        try {
            $res = $this->api()->timeout(90)->post('/convai/sip-trunk/outbound-call', $payload);
            $body = $res->json() ?: [];
            if ($res->ok() && ($body['success'] ?? false)) {
                return [true, (string) ($body['conversation_id'] ?? '')];
            }
            $msg = $body['message'] ?? ('HTTP ' . $res->status());
            if (str_contains($msg, 'not available')) {
                $msg .= ' (aam taur par credits khatam hain)';
            }
            return [false, $msg];
        } catch (\Throwable $e) {
            return [false, 'timeout/error: ' . $e->getMessage()];
        }
    }

    /** status + duration of a conversation: [state, durationSecs] where state is active|done|failed|unknown */
    public function conversationState(string $conversationId): array
    {
        try {
            $c = $this->api()->timeout(20)->get('/convai/conversations/' . $conversationId)->json() ?: [];
            $status = $c['status'] ?? 'unknown';
            $dur = (int) ($c['metadata']['call_duration_secs'] ?? 0);
            if (in_array($status, ['done'], true)) {
                return ['done', $dur];
            }
            if (in_array($status, ['failed'], true)) {
                return ['failed', $dur];
            }
            if (in_array($status, ['initiated', 'in-progress', 'processing'], true)) {
                return ['active', $dur];
            }
            return ['unknown', $dur];
        } catch (\Throwable $e) {
            return ['unknown', 0];
        }
    }
}
