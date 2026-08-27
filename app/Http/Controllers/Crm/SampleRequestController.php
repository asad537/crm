<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SampleOrder;
use Illuminate\Support\Facades\Log;

class SampleRequestController extends Controller
{
    /**
     * Display a listing of sample requests
     */
    public function index(Request $request)
    {
        try {
            $query = SampleOrder::orderBy('created_at', 'desc');

            // Filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('contact_name', 'like', "%{$search}%")
                      ->orWhere('contact_email', 'like', "%{$search}%")
                      ->orWhere('contact_phone', 'like', "%{$search}%");
                });
            }

            $samples = $query->paginate(15);
            return view('crm.samples.index', compact('samples'));
        } catch (\Exception $e) {
            Log::error('Crm SampleRequestController@index Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load sample requests.');
        }
    }

    /**
     * Show details of a sample request
     */
    public function show($id)
    {
        try {
            $sample = SampleOrder::findOrFail($id);
            return view('crm.samples.show', compact('sample'));
        } catch (\Exception $e) {
            return back()->with('error', 'Sample request not found.');
        }
    }

    /**
     * Update sample status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:processed,produced,shipping,out_for_delivery,delivered,cancelled'
        ]);

        try {
            $sample = SampleOrder::findOrFail($id);
            $sample->update(['status' => $request->status]);
            return back()->with('success', 'Status updated to ' . ucfirst($request->status));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update status.');
        }
    }

    /**
     * Update pricing and activate payment summary
     */
    public function updatePricing(Request $request, $id)
    {
        $request->validate([
            'unit_price' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0'
        ]);

        try {
            $sample = SampleOrder::findOrFail($id);
            $sample->update([
                'unit_price' => $request->unit_price,
                'delivery_fee' => $request->delivery_fee,
                'is_price_provided' => true
            ]);
            return back()->with('success', 'Pricing updated and activated for user.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update pricing.');
        }
    }

    /**
     * Delete a sample request
     */
    public function destroy($id)
    {
        try {
            $sample = SampleOrder::findOrFail($id);
            $sample->delete();
            return redirect()->route('crm.samples.index')->with('success', 'Sample request deleted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete sample request.');
        }
    }
}
