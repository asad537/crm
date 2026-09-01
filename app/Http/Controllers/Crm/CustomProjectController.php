<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\CustomProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomProjectController extends Controller
{
    /**
     * Display a listing of the custom projects for CRM.
     *
     */
    public function index(Request $request)
    {
        try {
            $filter = $request->get('date_filter', 'all');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = CustomProject::with(['user', 'dielines.mockups', 'sampleOrder', 'productionOrders'])
                ->orderBy('created_at', 'desc');

            // Custom range takes priority
            if ($dateFrom && $dateTo) {
                $query->whereBetween('created_at', [
                    \Carbon\Carbon::parse($dateFrom)->startOfDay(),
                    \Carbon\Carbon::parse($dateTo)->endOfDay(),
                ]);
            } else {
                switch ($filter) {
                    case 'today':
                        $query->whereDate('created_at', now()->toDateString());
                        break;
                    case 'yesterday':
                        $query->whereDate('created_at', now()->subDay()->toDateString());
                        break;
                    case 'this_week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'this_month':
                        $query->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                        break;
                    default:
                        break;
                }
            }

            $projects = $query->paginate(20)->appends(['date_filter' => $filter, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

            // ── STATS: queried from full DB (not paginated page) ──────────
            $statsQuery = CustomProject::query();
            if ($dateFrom && $dateTo) {
                $statsQuery->whereBetween('created_at', [
                    \Carbon\Carbon::parse($dateFrom)->startOfDay(),
                    \Carbon\Carbon::parse($dateTo)->endOfDay(),
                ]);
            } else {
                switch ($filter) {
                    case 'today':
                        $statsQuery->whereDate('created_at', now()->toDateString());
                        break;
                    case 'yesterday':
                        $statsQuery->whereDate('created_at', now()->subDay()->toDateString());
                        break;
                    case 'this_week':
                        $statsQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'this_month':
                        $statsQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                        break;
                }
            }

            // Stats — merge simple counts (total + new) into ONE query;
            // whereHas/whereDoesntHave stay separate because their relations use different foreign-key column names.
            $simpleAgg = (clone $statsQuery)
                ->selectRaw("COUNT(*) as total_count, SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count")
                ->first();
            $totalCount = (int) ($simpleAgg->total_count ?? 0);
            $newCount   = (int) ($simpleAgg->new_count   ?? 0);
            $productionCount = (clone $statsQuery)->whereHas('productionOrders')->count();
            $designCount     = (clone $statsQuery)->whereDoesntHave('productionOrders')->whereDoesntHave('sampleOrder')->count();

            return view('crm.custom_projects.index', compact('projects', 'filter', 'totalCount', 'newCount', 'designCount', 'productionCount'));
        } catch (\Exception $e) {
            Log::error('Crm CustomProjectController@index Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load projects: ' . $e->getMessage());
        }
    }

    /**
     * Show project details with dielines and mockups
     */
    public function show($id)
    {
        try {
            $project = CustomProject::with(['dielines.mockups', 'sampleOrder', 'productionOrders'])->findOrFail($id);

            // Mark project as viewed if it's 'new'
            if ($project->status == 'new' || $project->status == '') {
                $project->update(['status' => 'viewed']);
            }

            // Mark any pending client dielines as viewed
            foreach ($project->dielines as $dieline) {
                if (!$dieline->is_company_upload && $dieline->status == 'pending') {
                    $dieline->update(['status' => 'viewed']);
                }
            }

            return view('crm.custom_projects.show', compact('project'));
        } catch (\Exception $e) {
            return back()->with('error', 'Project not found: ' . $e->getMessage());
        }
    }

    /**
     * Destroy a custom project
     */
    public function destroy($id)
    {
        try {
            $project = CustomProject::findOrFail($id);
            $project->delete();
            return redirect()->route('crm.app_projects')->with('success', 'Project deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete project: ' . $e->getMessage());
        }
    }

    /**
     * Upload dieline from CRM
     */
    public function uploadDieline(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'file_name' => 'nullable|string'
        ]);

        try {
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
            if (!file_exists($uploadDir))
                mkdir($uploadDir, 0755, true);
            $file->move($uploadDir, $filename);

            // file_name = 'Company Dieline' (display). 
            // Permanent flag: is_company_upload = true.
            \App\Dieline::create([
                'project_id' => $id,
                'file_name' => 'Company Dieline',
                'file_path' => 'uploads/dielines/' . $filename,
                'file_size' => $this->formatBytes($file->getSize()),
                'status' => 'pending',
                'is_company_upload' => true,
            ]);

            return back()->with('success', 'Dieline uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function updateDielineStatus(Request $request, $id)
    {
        try {
            $dieline = \App\Dieline::findOrFail($id);
            $dieline->update(['status' => $request->status]);
            return back()->with('success', 'Status updated to ' . $request->status);
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function renameDieline(Request $request, $id)
    {
        try {
            $dieline = \App\Dieline::findOrFail($id);
            $dieline->update(['file_name' => $request->file_name]);
            return back()->with('success', 'File renamed to ' . $request->file_name);
        } catch (\Exception $e) {
            return back()->with('error', 'Rename failed: ' . $e->getMessage());
        }
    }

    public function destroyDieline($id)
    {
        try {
            $dieline = \App\Dieline::findOrFail($id);
            if ($dieline->file_path && file_exists(public_path($dieline->file_path))) {
                unlink(public_path($dieline->file_path));
            }
            $dieline->delete();
            return back()->with('success', 'Dieline deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload mockup for a dieline from CRM
     */
    public function uploadMockup(Request $request, $dielineId)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'file_name' => 'nullable|string'
        ]);

        try {
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

            $filename = time() . '_mockup_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $originalName);

            $mockupDir = public_path('uploads/mockups');
            if (!file_exists($mockupDir))
                mkdir($mockupDir, 0755, true);
            $file->move($mockupDir, $filename);

            \App\Mockup::create([
                'dieline_id' => $dielineId,
                'file_name' => $originalName,
                'file_path' => 'uploads/mockups/' . $filename,
                'file_size' => $this->formatBytes($file->getSize()),
                'status' => 'pending',
                'is_company' => true,
            ]);

            return back()->with('success', 'Mockup uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Mockup upload failed: ' . $e->getMessage());
        }
    }

    public function updateMockupStatus(Request $request, $id)
    {
        try {
            $mockup = \App\Mockup::findOrFail($id);
            $updateData = ['status' => $request->status];
            if ($request->has('change_request_comment') && $request->change_request_comment) {
                $updateData['change_request_comment'] = $request->change_request_comment;
            }
            $mockup->update($updateData);
            return back()->with('success', 'Mockup status updated to ' . $request->status);
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroyMockup($id)
    {
        try {
            $mockup = \App\Mockup::findOrFail($id);
            if ($mockup->file_path && file_exists(public_path($mockup->file_path))) {
                unlink(public_path($mockup->file_path));
            }
            $mockup->delete();
            return back()->with('success', 'Mockup deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function fulfillMockupRequest(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        try {
            $mockup = \App\Mockup::findOrFail($id);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $filename = time() . '_mockup_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $originalName);

                $uploadDir = 'uploads/mockups/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $file->move($uploadDir, $filename);
                $path = 'uploads/mockups/' . $filename;
                $fileSize = $this->formatBytes($file->getSize());

                $mockup->update([
                    'file_name' => $originalName,
                    'file_path' => $path,
                    'file_size' => $fileSize,
                    'status' => 'pending',
                    'is_company' => true,
                ]);

                return redirect()->back()->with('success', 'Mockup request fulfilled successfully.');
            }

            return redirect()->back()->with('error', 'No file uploaded.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to fulfill request: ' . $e->getMessage());
        }
    }

    public function fulfillDielineRequest(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        try {
            $dieline = \App\Dieline::findOrFail($id);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $originalName);

                $uploadDir = public_path('uploads/dielines');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $file->move($uploadDir, $filename);
                $path = 'uploads/dielines/' . $filename;
                $fileSize = $this->formatBytes($file->getSize());

                // Permanent flag: is_company_upload = true. Naming no longer affects source detection.
                $dieline->update([
                    'file_path' => $path,
                    'file_size' => $fileSize,
                    'status' => 'pending',
                    'is_company_upload' => true,
                ]);

                return redirect()->back()->with('success', 'Dieline request fulfilled successfully.');
            }

            return redirect()->back()->with('error', 'No file uploaded.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to fulfill request: ' . $e->getMessage());
        }
    }

    public function updateProductionStatus(Request $request, $id)
    {
        try {
            $order = \App\ProductionOrder::findOrFail($id);
            $order->update(['status' => $request->status]);
            return back()->with('success', 'Production status updated to ' . $request->status);
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function updateProductionPricing(Request $request, $id)
    {
        try {
            $order = \App\ProductionOrder::findOrFail($id);
            $order->update([
                'unit_price' => $request->unit_price,
                'delivery_fee' => $request->delivery_fee,
                'is_price_provided' => true
            ]);
            return back()->with('success', 'Production pricing updated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroyProductionOrder($id)
    {
        try {
            $order = \App\ProductionOrder::findOrFail($id);
            $order->delete();
            return back()->with('success', 'Production order deleted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete order: ' . $e->getMessage());
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
