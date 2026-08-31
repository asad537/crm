<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\CustomProject;
use App\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\SendMail;

class ProjectApiController extends Controller
{
    /**
     * Store a new custom project
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {

            $data = $request->only([
                'project_name',
                'project_description',
                'category_name',
                'subcategory_name',
                'product_name',
                'material_name',
                'addon_name',
                'unit',
                'width',
                'height',
                'length',
                'message'
            ]);

            // Create project first to get ID or just create after processing images
            $project = new CustomProject();
            $project->fill($data);

            if ($request->has('firebase_uid') && $request->input('firebase_uid') !== 'null' && $request->input('firebase_uid') !== '') {
                $user = \App\User::where('firebase_uid', $request->input('firebase_uid'))->first();
                if ($user) {
                    $project->user_id = $user->id;
                }
            } else if ($request->user()) {
                $project->user_id = $request->user()->id;
            }

            // Handle Image Uploads and URLs
            $imageFields = [
                'subcategory_image' => 'subcategory_image_url',
                'product_image' => 'product_image_url',
                'material_image' => 'material_image_url',
                'addon_image' => 'addon_image_url'
            ];

            // Map Flutter asset folder to server folder (root images/ directory, outside public)
            $assetFolderMap = [
                'img_materials'    => 'images/app_assets/materials',
                'img_addons'       => 'images/app_assets/addons',
                'img_finishing'    => 'images/app_assets/addons',
                'img_box_Features' => 'images/app_assets/addons',
            ];

            foreach ($imageFields as $dbField => $urlField) {
                // If it's a file upload from Flutter (bytes)
                if ($request->hasFile($dbField)) {
                    $file = $request->file($dbField);
                    $filename = time() . '_' . $dbField . '_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $file->getClientOriginalName());
                    // Save below the public web root so the legacy image URL remains reachable.
                    $uploadPath = public_path('images/projects');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    $file->move($uploadPath, $filename);
                    $project->$dbField = url('images/projects/' . $filename);
                }
                // If it's already a full HTTP URL
                else if ($request->filled($urlField) && Str::startsWith($request->input($urlField), 'http')) {
                    $project->$dbField = $request->input($urlField);
                }
                // If it's a Flutter asset path like assets/img_materials/holographic.webp
                else if ($request->filled($dbField) && Str::startsWith($request->input($dbField), 'assets/')) {
                    $assetPath = $request->input($dbField);
                    $parts = explode('/', $assetPath);
                    $folder = $parts[1] ?? '';
                    $fileName = $parts[count($parts) - 1];
                    $serverFolder = $assetFolderMap[$folder] ?? 'images/app_assets/materials';
                    $project->$dbField = url($serverFolder . '/' . $fileName);
                }
                // Fallback: plain string URL or path
                else if ($request->filled($urlField) || $request->filled($dbField)) {
                    $project->$dbField = $request->input($urlField) ?: $request->input($dbField);
                }
            }

            $project->save();

            $project->project_id_formatted = 'MBP-' . str_pad($project->id + 9000, 4, '0', STR_PAD_LEFT);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully!',
                'data' => $project
            ], 201);

        } catch (\Throwable $e) {
            Log::error('ProjectApiController@store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent production orders (optionally filtered by product)
     */
    public function indexOrders(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            $userId = $request->get('user_id');
            $firebaseUid = $request->get('firebase_uid');

            \Log::info("Fetching production orders for product_id: $productId, user_id: $userId, firebase_uid: $firebaseUid");

            $query = ProductionOrder::orderBy('created_at', 'desc');

            if ($productId) {
                $query->where('project_id', $productId);
            } else {
                // Require firebase_uid - never return all orders to unauthenticated requests
                if (!$firebaseUid || $firebaseUid === 'null') {
                    return response()->json(['success' => true, 'data' => []], 200);
                }
                $user = \App\User::where('firebase_uid', $firebaseUid)->first();
                if (!$user) {
                    return response()->json(['success' => true, 'data' => []], 200);
                }
                $query->where('user_id', $user->id);
            }

            $orders = $query->get();

            return response()->json([
                'success' => true,
                'data' => $orders
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific production order details with timeline
     */
    public function showOrder($id)
    {
        try {
            $order = ProductionOrder::findOrFail($id);

            // Format timeline based on status
            $timeline = $this->generateOrderTimeline($order);

            return response()->json([
                'success' => true,
                'data' => $order,
                'timeline' => $timeline
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
    }

    /**
     * Helper to generate production order timeline steps
     */
    private function generateOrderTimeline($order)
    {
        $stages = [
            'processed' => 'Admin confirms the specs.',
            'produced'  => 'Manufacturing process has started.',
            'shipping'  => 'Order has been handed over to courier.',
            'delivered' => 'Final delivery confirmed.'
        ];

        $currentStatus = $order->status;
        $orderKeys = ['processed', 'produced', 'shipping', 'delivered'];

        // activeIndex = which step is currently "active" (orange)
        // steps BEFORE activeIndex are completed (green)
        $activeMap = [
            'pending_review'  => 0, // In Process (orange on Processed)
            'payment_pending' => 0,
            'processed'       => 1, // Processed done → In Production active
            'in_process'      => 1,
            'produced'        => 2, // Produced done → Shipping active
            'in_production'   => 2,
            'quality_check'   => 2,
            'shipping'        => 3, // Shipping done → Delivered active
            'shipped'         => 3,
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
                'date'   => $showDate ? $order->updated_at->format('M d, Y') : null,
            ];
        }

        return $timeline;
    }

    /**
     * Get all custom projects
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = CustomProject::with(['productionOrders', 'dielines.mockups', 'sampleOrder'])->orderBy('created_at', 'desc');

            // Require firebase_uid - never return all projects to unauthenticated requests
            if (!$request->has('firebase_uid') || $request->input('firebase_uid') === 'null' || $request->input('firebase_uid') === '') {
                return response()->json(['success' => true, 'data' => []], 200);
            }

            $user = \App\User::where('firebase_uid', $request->input('firebase_uid'))->first();
            if (!$user) {
                return response()->json(['success' => true, 'data' => []], 200);
            }

            $query->where('user_id', $user->id);

            $projects = $query->get();

            $formattedProjects = $projects->map(function ($project) {
                // Latest Production Order
                $latestOrder = $project->productionOrders->last();

                // 1. Dieline Stage
                $stage = 'Dieline';
                $progress = 0;

                // 2. Mockup Stage (if dieline is completed or mockup exists)
                $hasMockup = false;
                if ($project->dielines && $project->dielines->isNotEmpty()) {
                    foreach ($project->dielines as $dieline) {
                        if ($dieline->mockups && $dieline->mockups->isNotEmpty()) {
                            $hasMockup = true;
                            break;
                        }
                        if (in_array(strtolower($dieline->status), ['approved', 'completed', 'accepted'])) {
                            $hasMockup = true;
                            break;
                        }
                    }
                }

                if ($hasMockup) {
                    $stage = 'Mockup';
                    $progress = 25;
                }

                // 3. Sample Stage
                if ($project->sampleOrder) {
                    $stage = 'Sample';
                    $progress = 50;
                }

                // 4. Order Stage
                if ($latestOrder) {
                    $stage = 'Order';
                    $progress = 75;
                    
                    // 5. Delivered Stage
                    if ($latestOrder->status === 'delivered') {
                        $stage = 'Delivered';
                        $progress = 100;
                    }
                }

                return [
                    'id' => $project->id,
                    'project_id_formatted' => 'MBP-' . str_pad($project->id + 9000, 4, '0', STR_PAD_LEFT),
                    'project_name' => $project->project_name,
                    'project_description' => $project->project_description ?? 'No description provided.',
                    'stage' => $stage,
                    'progress' => $progress,
                    'updated_at' => $project->updated_at ? $project->updated_at->diffForHumans() : 'Recently',
                    'category_name' => $project->category_name,
                    'subcategory_name' => $project->subcategory_name,
                    'product_name' => $project->product_name,
                    'material_name' => $project->material_name,
                    'addon_name' => $project->addon_name,
                    'product_image' => $project->product_image ? url($project->product_image) : null,
                    'subcategory_image' => $project->subcategory_image ? url($project->subcategory_image) : null,
                    'material_image' => $project->material_image ? url($project->material_image) : null,
                    'addon_image' => $project->addon_image ? url($project->addon_image) : null,
                    'width' => $project->width ? (string) $project->width : null,
                    'height' => $project->height ? (string) $project->height : null,
                    'length' => $project->length ? (string) $project->length : null,
                    'unit' => $project->unit,
                    'message' => $project->message,
                    'production_order' => $latestOrder ? [
                        'id' => $latestOrder->id,
                        'status' => $latestOrder->status,
                        'production_type' => $latestOrder->production_type,
                        'quantity' => $latestOrder->quantity,
                        'shipping_address' => $latestOrder->shipping_address ?? '',
                        'billing_address' => $latestOrder->billing_address ?? '',
                        'unit_price' => $latestOrder->unit_price,
                        'delivery_fee' => $latestOrder->delivery_fee,
                        'is_price_provided' => $latestOrder->is_price_provided,
                        'created_at' => $latestOrder->created_at->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProjects
            ], 200);

        } catch (\Exception $e) {
            Log::error('ProjectApiController@index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Place a production order for a project
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder(Request $request, $projectId)
    {
        try {
            $project = CustomProject::find($projectId);
            if (!$project) {
                return response()->json(['success' => false, 'message' => 'Project not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'production_type' => 'required|string|in:plain,printed',
                'contact_name' => 'required|string|max:255',
                'contact_phone' => 'required|string|max:20',
                'contact_email' => 'required|email|max:255',
                'quantity' => 'required|integer|min:1',
                'shipping_address' => 'required|string',
                'billing_address' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $orderData = $request->only([
                'production_type',
                'contact_name',
                'contact_phone',
                'contact_email',
                'quantity',
                'shipping_address',
                'billing_address'
            ]);

            $orderData['project_id'] = $projectId;
            $orderData['status'] = 'pending_review';

            if ($request->has('firebase_uid') && $request->input('firebase_uid') !== 'null' && $request->input('firebase_uid') !== '') {
                $user = \App\User::where('firebase_uid', $request->input('firebase_uid'))->first();
                if ($user) {
                    $orderData['user_id'] = $user->id;
                }
            }
            if (!isset($orderData['user_id'])) {
                $orderData['user_id'] = $project->user_id ?: ($request->user() ? $request->user()->id : null);
            }

            $order = \App\ProductionOrder::create($orderData);

            return response()->json([
                'success' => true,
                'message' => 'Production order placed successfully!',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ProjectApiController@placeOrder Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to place order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel a production order
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrder($id)
    {
        try {
            $order = ProductionOrder::findOrFail($id);

            if ($order->status === 'cancelled') {
                return response()->json(['success' => false, 'message' => 'Order is already cancelled'], 400);
            }

            $order->status = 'cancelled';
            $order->save();

            // Send notification email to admin
            $data = [
                'subject' => 'order_cancel',
                'id' => $id,
                'order_no' => 'PO-' . (5000 + $id),
                'type' => 'Production Order',
                'customer_name' => $order->contact_name,
                'customer_email' => $order->contact_email,
                'email' => 'quotes@myboxprinting.com'
            ];

            try {
                Mail::to('quotes@myboxprinting.com')->send(new SendMail($data));
            } catch (\Exception $e) {
                Log::error('Mail Error in ProjectApiController@cancelOrder: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('ProjectApiController@cancelOrder Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to cancel order'], 500);
        }
    }
}
