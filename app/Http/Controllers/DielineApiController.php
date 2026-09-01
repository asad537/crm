<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Dieline;
use App\Mockup;
use App\CustomProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DielineApiController extends Controller
{
    /**
     * Get all dielines for a specific project
     */
    public function index($projectId)
    {
        try {
            $project = CustomProject::with(['dielines.mockups'])->findOrFail($projectId);
            
            $dielines = $project->dielines->map(function ($dieline) {
                $fileUrl = null;
                if ($dieline->file_path) {
                    $fileUrl = rtrim(config('app.url'), '/') . '/' . ltrim($dieline->file_path, '/');
                }

                $isCompany = (bool)$dieline->is_company_upload;

                $fullFileName = $dieline->file_path ? basename($dieline->file_path) : $dieline->file_name;
                
                // Strip leading timestamp and underscore (e.g., 1775541707_file.pdf -> file.pdf)
                $cleanFileName = preg_replace('/^\d+_/', '', $fullFileName);

                return [
                    'id'          => $dieline->id,
                    'title'       => $isCompany ? 'Company Dieline' : 'User Dieline',
                    'is_company'  => $isCompany,
                    'file_name'   => $cleanFileName,
                    'file_url'    => $fileUrl,
                    'file_size'   => $dieline->file_size ?? 'Pending',
                    'status'      => $dieline->status,
                    'upload_date' => $dieline->created_at->diffForHumans(),
                    'mockups' => $dieline->mockups->map(function ($mockup) {
                        $mockupUrl = null;
                        if ($mockup->file_path) {
                            $mockupUrl = rtrim(config('app.url'), '/') . '/' . ltrim($mockup->file_path, '/');
                        }
                        return [
                            'id'                     => $mockup->id,
                            'title'                  => $mockup->is_company ? 'Company Mockup' : 'User Mockup',
                            'file_name'              => $mockup->file_name,
                            'file_url'               => $mockupUrl,
                            'file_size'              => $mockup->file_size ?? 'N/A',
                            'status'                 => $mockup->status,
                            'is_company'             => (bool)$mockup->is_company,
                            'change_request_comment' => $mockup->change_request_comment,
                            'upload_date'            => $mockup->created_at->diffForHumans(),
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $dielines
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dielines',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload a new dieline
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:custom_projects,id',
            'file' => 'nullable|file|max:10240',
            'file_name' => 'nullable|string',
            'comment' => 'nullable|string',
            'change_request_comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Log incoming request for debugging
            \Log::info("Dieline Upload Request Received", $request->all());

            $path = null;
            $fileSize = null;
            $originalName = $request->input('file_name');
            $userComment = $request->input('comment') ?? $request->input('change_request_comment');

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $customName = $request->input('file_name');
                
                if ($customName) {
                    if (!preg_match('/\.[a-zA-Z0-9]+$/', $customName) && $extension) {
                        $customName = $customName . '.' . $extension;
                    }
                    $originalName = $customName;
                } else {
                    $originalName = $file->getClientOriginalName();
                }

                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $originalName);
                $uploadDir = public_path('uploads/dielines');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $file->move($uploadDir, $filename);
                $path = 'uploads/dielines/' . $filename;
                $fileSize = $this->formatBytes($file->getSize());

                $dieline = Dieline::create([
                    'project_id' => $request->project_id,
                    'file_name'  => $originalName,
                    'file_path'  => $path,
                    'file_size'  => $fileSize,
                    'status'     => 'pending',
                    'is_company_upload' => false,
                    'change_request_comment' => $userComment,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Dieline uploaded successfully',
                    'data'    => $dieline
                ], 201);
            }

            // No file path logic (Request Design)
            $dieline = Dieline::create([
                'project_id' => $request->project_id,
                'file_name'  => 'Design Requested',
                'file_path'  => null,
                'file_size'  => 'Pending',
                'status'     => 'pending_company_design',
                'change_request_comment' => $userComment,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Design request sent to company.',
                'data'    => $dieline,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update dieline status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,change_requested,unapproved',
            'comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $dieline = Dieline::findOrFail($id);
            $updateData = ['status' => $request->status];
            
            if ($request->has('comment')) {
                $updateData['change_request_comment'] = $request->comment;
            }

            $dieline->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $dieline
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Rename dieline file
     */
    public function rename(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $dieline = Dieline::findOrFail($id);
            $dieline->update(['file_name' => $request->file_name]);

            return response()->json([
                'success' => true,
                'message' => 'File renamed successfully',
                'data' => $dieline
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete dieline
     */
    public function destroy($id)
    {
        try {
            $dieline = Dieline::findOrFail($id);
            
            // Delete physical file
            if ($dieline->file_path && file_exists(public_path($dieline->file_path))) {
                unlink(public_path($dieline->file_path));
            }

            $dieline->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dieline deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload a mockup from the mobile app (user-side upload)
     */
    public function uploadMockupFromApp(Request $request, $dielineId)
    {
        $validator = Validator::make($request->all(), [
            'file'    => 'required|file|max:25600', // 25MB max
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Verify dieline exists
            $dieline = Dieline::findOrFail($dielineId);

            $file        = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension   = $file->getClientOriginalExtension();
            $filename    = time() . '_user_mockup_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $originalName);

            $mockupDir = public_path('uploads/mockups');
            if (!file_exists($mockupDir)) {
                mkdir($mockupDir, 0755, true);
            }

            $file->move($mockupDir, $filename);
            $filePath = 'uploads/mockups/' . $filename;
            $fileSize = $this->formatBytes($file->getSize());

            $mockup = Mockup::create([
                'dieline_id'             => $dielineId,
                'file_name'              => $originalName,
                'file_path'              => $filePath,
                'file_size'              => $fileSize,
                'status'                 => 'pending',
                'is_company'             => false,
                'change_request_comment' => $request->input('comment'),
            ]);

            $mockupUrl = rtrim(config('app.url'), '/') . '/' . ltrim($filePath, '/');

            Log::info('User mockup uploaded via app', [
                'dieline_id' => $dielineId,
                'mockup_id'  => $mockup->id,
                'file'       => $originalName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mockup uploaded successfully',
                'data'    => [
                    'id'         => $mockup->id,
                    'file_name'  => $mockup->file_name,
                    'file_url'   => $mockupUrl,
                    'file_size'  => $mockup->file_size,
                    'status'     => $mockup->status,
                    'is_company' => false,
                    'upload_date' => $mockup->created_at->diffForHumans(),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('User mockup upload failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateMockupStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status'  => 'required|in:pending,approved,change_requested,unapproved',
            'comment' => 'nullable|string',
            'change_request_comment' => 'nullable|string',
        ]);

        Log::info('Mockup status update requested', ['mockup_id' => $id, 'status' => $request->status]);

        if ($validator->fails()) {
            Log::warning('Mockup status validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $mockup = \App\Mockup::findOrFail($id);

            $updateData = ['status' => $request->status];

            // Save comment when customer requests change
            $comment = $request->input('change_request_comment') ?? $request->input('comment');
            if ($comment) {
                $updateData['change_request_comment'] = $comment;
            }

            // Clear comment when approved or unapproved
            if (in_array($request->status, ['approved', 'unapproved', 'pending'])) {
                // Keep the old comment — don't clear it so CRM can still see it
            }

            $mockup->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Mockup status updated successfully',
                'data'    => $mockup
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
