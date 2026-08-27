<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\SampleOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;

class SampleOrderController extends Controller
{
    /**
     * Submit a new sample request
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sample_type' => 'required|string|in:plain,printed',
                'contact_name' => 'required|string|max:255',
                'contact_phone' => 'required|string|max:20',
                'contact_email' => 'required|email|max:255',
                'quantity' => 'required|integer|min:1',
                'shipping_address' => 'required|string',
                'billing_address' => 'required|string',
                'product_id' => 'nullable|integer',
                'user_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $orderData = $request->all();
            $orderData['status'] = 'pending_review';

            // Link to CRM user via firebase_uid
            if (!empty($orderData['firebase_uid'])) {
                $user = \App\User::where('firebase_uid', $orderData['firebase_uid'])->first();
                if ($user) {
                    $orderData['user_id'] = $user->id;
                }
                unset($orderData['firebase_uid']); // Remove from data, not a DB column
            }

            $sampleOrder = SampleOrder::create($orderData);

            return response()->json([
                'success' => true,
                'message' => 'Sample request submitted successfully!',
                'data' => $sampleOrder
            ], 201);

        } catch (\Exception $e) {
            Log::error('SampleOrderController@store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit sample request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's sample orders
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            $email = $request->get('email');
            $productId = $request->get('product_id');
            $firebaseUid = $request->get('firebase_uid');

            $query = SampleOrder::orderBy('created_at', 'desc');

            if ($productId) {
                $query->where('product_id', $productId);
            } elseif ($firebaseUid && $firebaseUid !== 'null') {
                $user = \App\User::where('firebase_uid', $firebaseUid)->first();
                if (!$user) {
                    return response()->json(['success' => true, 'data' => []], 200);
                }
                $query->where('user_id', $user->id);
            } elseif ($userId) {
                $query->where('user_id', $userId);
            } elseif ($email) {
                $query->where('contact_email', $email);
            } else {
                // No identifier provided - return empty for security
                return response()->json(['success' => true, 'data' => []], 200);
            }

            $samples = $query->get();

            return response()->json([
                'success' => true,
                'data' => $samples
            ], 200);

        } catch (\Exception $e) {
            Log::error('SampleOrderController@index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sample orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific sample tracking details
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $sample = SampleOrder::findOrFail($id);

            // Format timeline based on status
            $timeline = $this->generateTimeline($sample);

            return response()->json([
                'success' => true,
                'data' => $sample,
                'timeline' => $timeline
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sample request not found'
            ], 404);
        }
    }

    /**
     * Helper to generate timeline steps
     */
    private function generateTimeline($sample)
    {
        $stages = [
            'processed' => 'Admin confirms the specs.',
            'produced'  => 'Sample manufacturing is complete.',
            'shipping'  => 'Tracking number is assigned.',
            'delivered' => 'Final package arrival.'
        ];

        $currentStatus = $sample->status;
        $orderKeys = ['processed', 'produced', 'shipping', 'delivered'];

        // activeIndex = which step is currently "active" (orange)
        // steps BEFORE activeIndex are completed (green)
        $activeMap = [
            'pending_review'  => 0, // In Process on Processed step
            'processed'       => 1, // Processed done → Produced active
            'produced'        => 2, // Produced done → Shipping active
            'shipping'        => 3, // Shipping done → Delivered active
            'out_for_delivery'=> 3,
            'delivered'       => 4, // All completed
            'cancelled'       => -1,
        ];

        $activeIndex  = array_key_exists($currentStatus, $activeMap) ? $activeMap[$currentStatus] : 0;
        $isCancelled  = $currentStatus === 'cancelled';
        $allCompleted = $activeIndex >= count($orderKeys);

        $timeline = [];
        foreach ($orderKeys as $index => $stage) {
            if ($isCancelled) {
                $stepStatus = 'pending';
            } elseif ($allCompleted) {
                $stepStatus = 'completed';
            } elseif ($index < $activeIndex) {
                $stepStatus = 'completed';
            } elseif ($index === $activeIndex) {
                $stepStatus = 'active';
            } else {
                $stepStatus = 'pending';
            }

            $showDate = $stepStatus === 'completed' || $stepStatus === 'active';

            $timeline[] = [
                'stage'  => ucfirst($stage),
                'action' => $stages[$stage],
                'status' => $stepStatus,
                'date'   => $showDate ? $sample->updated_at->format('M d, Y') : null,
            ];
        }

        return $timeline;
    }
    /**
     * Cancel a sample request
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSample($id)
    {
        try {
            $sample = SampleOrder::findOrFail($id);

            if ($sample->status === 'cancelled') {
                return response()->json(['success' => false, 'message' => 'Sample is already cancelled'], 400);
            }

            $sample->status = 'cancelled';
            $sample->save();

            // Send notification email to admin
            $data = [
                'subject' => 'order_cancel',
                'id' => $id,
                'order_no' => 'SR-' . (1000 + $id),
                'type' => 'Sample Request',
                'customer_name' => $sample->contact_name,
                'customer_email' => $sample->contact_email,
                'email' => 'quotes@myboxprinting.com'
            ];

            try {
                Mail::to('quotes@myboxprinting.com')->send(new SendMail($data));
            } catch (\Exception $e) {
                Log::error('Mail Error in SampleOrderController@cancelSample: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Sample request cancelled successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('SampleOrderController@cancelSample Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to cancel sample request'], 500);
        }
    }
}
