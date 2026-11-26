<?php

namespace App\Console\Commands;

use App\CrmEmail;
use App\Services\VoiceCallService;
use App\VoiceCallQueue;
use Illuminate\Console\Command;

/**
 * Works the Sara follow-up call queue ONE call at a time (the SIP trunk handles a single
 * call reliably, and this paces credits). Run from host cron every minute:
 *   docker exec crm_l10_app php artisan voice:process-queue
 * Each run: settle in-flight calls, then place at most one new call.
 */
class ProcessVoiceCallQueue extends Command
{
    protected $signature = 'voice:process-queue';
    protected $description = 'Place the next queued Sara follow-up call and settle finished ones';

    public function handle(VoiceCallService $voice): int
    {
        if (!$voice->configured()) {
            $this->warn('ElevenLabs env vars missing; skipping.');
            return self::SUCCESS;
        }

        // 1) Settle in-flight calls.
        $busy = false;
        foreach (VoiceCallQueue::where('status', 'calling')->get() as $row) {
            if (!$row->conversation_id) {
                $row->update(['status' => 'failed', 'note' => 'calling without conversation_id']);
                continue;
            }
            [$state, $dur, $transcript] = $voice->conversationData($row->conversation_id);
            if ($state === 'done') {
                $note = null;
                $outcome = $voice->analyzeOutcome($transcript);
                if ($outcome) {
                    $note = trim('outcome: ' . ($outcome['outcome'] ?? 'unclear')
                        . (!empty($outcome['callback_time']) ? ' (callback: ' . $outcome['callback_time'] . ')' : '')
                        . ' — ' . ($outcome['summary'] ?? ''));
                    $this->applyOutcomeToLead($row->crm_email_id, $outcome);
                }
                $row->update(['status' => 'done', 'duration_secs' => $dur, 'note' => $note ? mb_substr($note, 0, 480) : $row->note]);
                $this->info("done: lead {$row->crm_email_id} ({$dur}s)" . ($note ? " {$note}" : ''));
            } elseif ($state === 'failed') {
                $row->update(['status' => 'failed', 'note' => 'call failed (busy / no answer / rejected)']);
                $this->info("failed: lead {$row->crm_email_id}");
            } elseif ($row->updated_at && $row->updated_at->lt(now()->subMinutes(20))) {
                $row->update(['status' => 'failed', 'note' => 'timed out after 20 min']);
            } else {
                $busy = true; // still ringing or talking
            }
        }
        if ($busy) {
            $this->info('a call is still in progress; waiting.');
            return self::SUCCESS;
        }

        // 2) Place at most one new call.
        $next = VoiceCallQueue::where('status', 'pending')->orderBy('id')->first();
        if (!$next) {
            return self::SUCCESS;
        }
        $lead = CrmEmail::withoutGlobalScopes()->find($next->crm_email_id);
        $to = $voice->normalizePhone($next->phone);
        if (!$lead || !$to) {
            $next->update(['status' => 'failed', 'note' => $lead ? 'phone number invalid' : 'lead not found']);
            return self::SUCCESS;
        }
        $next->update(['status' => 'calling']); // claim before the slow API call
        [$ok, $result] = $voice->placeCall($to, $lead);
        if ($ok) {
            $next->update(['conversation_id' => $result]);
            $this->info("calling lead {$lead->id} ({$to}): {$result}");
        } else {
            $next->update(['status' => 'failed', 'note' => mb_substr($result, 0, 480)]);
            $this->warn("place failed for lead {$lead->id}: {$result}");
        }

        return self::SUCCESS;
    }

    /** Push the call outcome onto the CRM lead: status upgrade on interest + a dated note. */
    private function applyOutcomeToLead(int $leadId, array $outcome): void
    {
        $lead = CrmEmail::withoutGlobalScopes()->find($leadId);
        if (!$lead) {
            return;
        }
        try {
            if (($outcome['outcome'] ?? '') === 'interested'
                && in_array($lead->status, ['New', 'Viewed', 'Client Replied', 'Responded'], true)) {
                $lead->status = 'Qualified Lead';
            }
            $note = '[Sara call ' . now()->format('d M Y H:i') . '] '
                . ($outcome['outcome'] ?? 'unclear')
                . (!empty($outcome['callback_time']) ? ' | callback: ' . $outcome['callback_time'] : '')
                . (!empty($outcome['summary']) ? ' | ' . $outcome['summary'] : '');
            $lead->message = mb_substr(trim((string) $lead->message . "\n\n" . $note), 0, 60000);
            $lead->save();
        } catch (\Throwable $e) {
            $this->warn("lead {$leadId} outcome update failed: {$e->getMessage()}");
        }
    }
}
