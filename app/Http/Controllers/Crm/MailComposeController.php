<?php

namespace App\Http\Controllers\Crm;

use App\CrmMailAccount;
use App\CrmMailMessage;
use App\Http\Controllers\Controller;
use App\Services\Mail\OutgoingMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Sending from a connected mailbox: reply / reply-all / forward to a synced message,
 * or a brand-new message. Only the mailbox OWNER may send (CrmMailAccountPolicy@send);
 * admins' read-only access never includes sending.
 */
class MailComposeController extends Controller
{
    private const ATTACH_RULE = 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip|max:10240';

    private function user()
    {
        return Auth::guard('crm')->user();
    }

    private function rules(bool $needsTo): array
    {
        return [
            'to' => [$needsTo ? 'required' : 'nullable', 'string', 'max:2000'],
            'cc' => ['nullable', 'string', 'max:2000'],
            'bcc' => ['nullable', 'string', 'max:2000'],
            'subject' => ['nullable', 'string', 'max:998'],
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:500000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [self::ATTACH_RULE],
        ];
    }

    private function present(CrmMailMessage $m): array
    {
        return [
            'id' => $m->id, 'account_id' => $m->account_id, 'thread_id' => $m->thread_id, 'crm_email_id' => $m->crm_email_id,
            'subject' => $m->subject, 'from_name' => $m->from_name, 'from_email' => $m->from_email, 'to' => $m->to_json,
            'snippet' => $m->snippet, 'is_outgoing' => true, 'is_read' => true, 'has_attachments' => (bool) $m->has_attachments,
            'received_at' => optional($m->received_at)->toIso8601String(),
        ];
    }

    /** POST /crm/mail/compose — new message from one of the caller's mailboxes. */
    public function compose(Request $request, OutgoingMailService $mail)
    {
        $data = $request->validate(array_merge($this->rules(true), [
            'account_id' => ['required', 'integer'],
            'crm_email_id' => ['nullable', 'integer'],
        ]));
        $account = CrmMailAccount::withoutGlobalScopes()->findOrFail((int) $data['account_id']);
        Gate::forUser($this->user())->authorize('send', $account);

        try {
            $message = $mail->send($account, [
                'to' => $data['to'], 'cc' => $data['cc'] ?? [], 'bcc' => $data['bcc'] ?? [],
                'subject' => $data['subject'] ?? '', 'html' => $data['body'] ?? '',
                'attachments' => $request->file('attachments', []),
                'crm_email_id' => $data['crm_email_id'] ?? null,
                'sent_by' => $this->user(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => $this->present($message)]);
    }

    /** POST /crm/mail/messages/{id}/reply — mode: reply | reply_all | forward. */
    public function reply(Request $request, $id, OutgoingMailService $mail)
    {
        $original = CrmMailMessage::withoutGlobalScopes()->findOrFail((int) $id);
        $account = $original->account()->withoutGlobalScopes()->firstOrFail();
        Gate::forUser($this->user())->authorize('send', $account);

        $mode = $request->input('mode', 'reply');
        $request->validate(['mode' => ['required', Rule::in(['reply', 'reply_all', 'forward'])]]);
        $data = $request->validate($this->rules($mode === 'forward'));

        $own = strtolower((string) $account->email_address);
        if ($mode === 'forward') {
            $to = $data['to'];
            $cc = $data['cc'] ?? [];
        } else {
            // Default recipients from the original; the caller may override.
            $defaultTo = $original->is_outgoing
                ? array_column($original->to_json ?: [], 'email')
                : [$original->reply_to ?: $original->from_email];
            $to = $data['to'] ?? '';
            $to = trim($to) !== '' ? $to : implode(',', array_filter($defaultTo));
            $cc = $data['cc'] ?? '';
            if ($mode === 'reply_all' && trim((string) $cc) === '') {
                $others = array_merge(array_column($original->to_json ?: [], 'email'), array_column($original->cc_json ?: [], 'email'));
                $others = array_filter($others, fn ($e) => strtolower($e) !== $own && !in_array(strtolower($e), $mail->cleanAddresses($to), true));
                $cc = implode(',', $others);
            }
        }

        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '') {
            $base = preg_replace('/^\s*((re|fw|fwd)\s*:\s*)+/i', '', (string) $original->subject);
            $subject = ($mode === 'forward' ? 'Fwd: ' : 'Re: ') . $base;
        }

        $html = (string) ($data['body'] ?? '');
        if ($mode === 'forward') {
            $when = optional($original->received_at)->format('D, M j, Y \a\t g:i A');
            $html .= '<br><br><div style="border-left:3px solid #e2e8f0;padding-left:12px;color:#475569">'
                . '<div style="font-size:12px;color:#64748b;margin-bottom:8px">---------- Forwarded message ----------<br>'
                . 'From: ' . e($original->from_name ?: '') . ' &lt;' . e((string) $original->from_email) . '&gt;<br>'
                . 'Date: ' . e((string) $when) . '<br>Subject: ' . e((string) $original->subject) . '</div>'
                . ($original->html_body ?: nl2br(e((string) $original->text_body))) . '</div>';
        } elseif (trim((string) $original->text_body) !== '' || $original->html_body) {
            $when = optional($original->received_at)->format('D, M j, Y \a\t g:i A');
            $html .= '<br><br><div style="color:#64748b;font-size:12px">On ' . e((string) $when) . ', ' . e($original->from_name ?: (string) $original->from_email) . ' wrote:</div>'
                . '<blockquote style="border-left:3px solid #e2e8f0;margin:6px 0;padding-left:12px;color:#475569">'
                . ($original->html_body ?: nl2br(e((string) $original->text_body))) . '</blockquote>';
        }

        try {
            $message = $mail->send($account, [
                'to' => $to, 'cc' => $cc, 'bcc' => $data['bcc'] ?? [],
                'subject' => $subject, 'html' => $html,
                'attachments' => $request->file('attachments', []),
                'reply_to_message' => $mode === 'forward' ? null : $original,
                'forward_of' => $mode === 'forward' ? $original : null,
                'crm_email_id' => $original->crm_email_id,
                'sent_by' => $this->user(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => $this->present($message)]);
    }
}
