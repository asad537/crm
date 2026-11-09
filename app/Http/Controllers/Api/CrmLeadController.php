<?php

namespace App\Http\Controllers\Api;

use App\CrmEmail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CrmLeadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'external_lead_id' => 'nullable|string|max:191',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'product_name' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'unit' => 'nullable|string|max:30',
            'stock' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'coating' => 'nullable|string|max:255',
            'file_url' => 'nullable|string|max:2048',
            'message' => 'nullable|string|max:10000',
            'subject' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'is_spam' => 'nullable|boolean',
            'spam_reason' => 'nullable|string|max:1000',
        ]);

        $workspace = $request->attributes->get('crm_workspace');
        $attributes = [
            'workspace_id' => $workspace->id,
            'external_lead_id' => $data['external_lead_id'] ?? null,
            'source' => $data['source'] ?? $workspace->name,
            'client_name' => $data['name'],
            'client_email' => $data['email'],
            'client_phone' => $data['phone'] ?? null,
            'product_name' => $data['product_name'] ?? 'General Inquiry',
            'quantity' => $data['quantity'] ?? null,
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'unit' => $data['unit'] ?? null,
            'stock' => $data['stock'] ?? null,
            'color' => $data['color'] ?? null,
            'coating' => $data['coating'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'message' => $data['message'] ?? null,
            'subject' => $data['subject'] ?? ($data['product_name'] ?? 'New website inquiry'),
            'status' => $data['status'] ?? 'New',
            'is_spam' => (bool) ($data['is_spam'] ?? false),
            'spam_reason' => $data['spam_reason'] ?? null,
            'is_rejected' => false,
            'ip_address' => $request->ip(),
        ];

        if (!empty($data['external_lead_id'])) {
            $lead = CrmEmail::withoutGlobalScopes()->firstOrCreate([
                'workspace_id' => $workspace->id,
                'external_lead_id' => $data['external_lead_id'],
            ], $attributes);
            $created = $lead->wasRecentlyCreated;
        } else {
            $lead = CrmEmail::withoutGlobalScopes()->create($attributes);
            $created = true;
        }

        // Welcome email to the client the moment a NEW (non-spam) lead lands (e.g. from the AI chat).
        // Delivered through the sales agent's OWN mailbox (Sara) so it is from and replies to her.
        if ($created && !(bool) ($data['is_spam'] ?? false) && filter_var($lead->client_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $agent = \App\CrmUser::where('email', 'sara@myboxprinting.com')->first();
                $saraBox = \App\CrmMailAccount::withoutGlobalScopes()
                    ->where('email_address', 'sara@myboxprinting.com')->where('is_active', 1)->first();
                $welcomeHtml = (new \App\Mail\LeadWelcomeMail($lead, $agent))->render();
                $welcomeSubject = "Thanks for reaching out to Premium Boxes \u{2014} I'm here to help!";
                if ($saraBox) {
                    // Send through Sara's CONNECTED mailbox so the copy lands in her CRM "Sent" folder (DB + IMAP append).
                    app(\App\Services\Mail\OutgoingMailService::class)->send($saraBox, [
                        'to' => $lead->client_email,
                        'subject' => $welcomeSubject,
                        'html' => $welcomeHtml,
                        'crm_email_id' => $lead->id,
                    ]);
                    \Log::error('Lead welcome email SENT', ['lead_id' => $lead->id, 'to' => $lead->client_email, 'via' => 'mailbox#' . $saraBox->id]);
                } else {
                    // Fallback: no connected mailbox -> agent raw SMTP (will NOT appear in the Sent folder).
                    $mailer = 'smtp';
                    if ($agent && $agent->smtp_host && $agent->email_user && $agent->email_pass) {
                        config(['mail.mailers.agent_welcome' => [
                            'transport' => 'smtp',
                            'host' => $agent->smtp_host,
                            'port' => (int) ($agent->smtp_port ?: 587),
                            'encryption' => $agent->smtp_encryption ?: 'tls',
                            'username' => $agent->email_user,
                            'password' => $agent->email_pass,
                            'timeout' => 15,
                        ]]);
                        $mailer = 'agent_welcome';
                    }
                    \Mail::mailer($mailer)->to($lead->client_email)->send(new \App\Mail\LeadWelcomeMail($lead, $agent));
                    \Log::error('Lead welcome email SENT', ['lead_id' => $lead->id, 'to' => $lead->client_email, 'via' => $mailer]);
                }
            } catch (\Throwable $e) {
                \Log::error('Lead welcome email FAILED: ' . $e->getMessage(), ['lead_id' => $lead->id]);
            }
        }

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'created' => $created,
        ], $created ? 201 : 200);
    }
}
