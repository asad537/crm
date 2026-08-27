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
            'message' => 'nullable|string|max:10000',
            'subject' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
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
            'message' => $data['message'] ?? null,
            'subject' => $data['subject'] ?? ($data['product_name'] ?? 'New website inquiry'),
            'status' => 'New',
            'is_spam' => false,
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
