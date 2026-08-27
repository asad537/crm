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

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'created' => $created,
        ], $created ? 201 : 200);
    }
}
