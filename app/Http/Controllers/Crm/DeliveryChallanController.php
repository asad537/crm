<?php

namespace App\Http\Controllers\Crm;

use App\DeliveryChallan;
use App\DesignJob;
use App\Http\Controllers\Controller;
use App\Support\CrmWorkspaceContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryChallanController extends Controller
{
    protected function requireAccess()
    {
        $user = Auth::guard('crm')->user();
        abort_unless($user, 403);
        if (!$user->isDesigner() && !$user->isAdmin() && !$user->isSales()) {
            abort(403, 'You do not have access to delivery challans.');
        }
        return $user;
    }

    protected function findJob($id)
    {
        return DesignJob::where('workspace_id', CrmWorkspaceContext::id())->findOrFail($id);
    }

    protected function findChallan($id)
    {
        return DeliveryChallan::where('workspace_id', CrmWorkspaceContext::id())->findOrFail($id);
    }

    public function store(Request $request, $id)
    {
        $this->requireAccess();
        $job = $this->findJob($id);

        $data = $this->validatedData($request);
        $challan = DeliveryChallan::create(array_merge($data, [
            'workspace_id' => CrmWorkspaceContext::id(),
            'design_job_id' => $job->id,
            'challan_no' => $this->newChallanNo(),
            'challan_date' => ($data['challan_date'] ?? null) ?: now()->toDateString(),
            'job_no' => $job->job_number,
        ]));

        $job->update(['production_stage' => 'close']);

        return redirect()->route('crm.design_jobs.index')
            ->with('success', 'Challan ' . $challan->challan_no . ' saved.');
    }

    public function update(Request $request, $id)
    {
        $this->requireAccess();
        $challan = $this->findChallan($id);
        $this->findJob($challan->design_job_id);
        $challan->update($this->validatedData($request));

        return redirect()->route('crm.design_jobs.index')
            ->with('success', 'Challan ' . $challan->challan_no . ' updated.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'challan_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'vehicle_no' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string|max:1000',
            'po_reference' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:2000',
            'prepared_by' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'driver_contact' => 'nullable|string|max:255',
            'received_by' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.boxes_per_carton' => 'nullable|string|max:255',
            'items.*.total_cartons' => 'nullable|string|max:255',
            'items.*.carton_size' => 'nullable|string|max:255',
            'items.*.actual_wt' => 'nullable|string|max:255',
            'items.*.volumetric_wt' => 'nullable|string|max:255',
        ]);

        $items = [];
        foreach ($data['items'] ?? [] as $row) {
            $keys = ['description', 'boxes_per_carton', 'total_cartons', 'carton_size', 'actual_wt', 'volumetric_wt'];
            $clean = [];
            $empty = true;
            foreach ($keys as $k) {
                $v = trim((string) ($row[$k] ?? ''));
                $clean[$k] = $v;
                if ($v !== '') {
                    $empty = false;
                }
            }
            if (!$empty) {
                $items[] = $clean;
            }
        }

        $data['items'] = $items;

        return $data;
    }

    public function print($id)
    {
        $this->requireAccess();
        $challan = $this->findChallan($id);

        return view('crm.design_jobs.delivery_challan', [
            'challan' => $challan,
            'print' => true,
        ]);
    }

    public function pdf($id)
    {
        $this->requireAccess();
        $challan = $this->findChallan($id);

        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', $challan->challan_no ?: 'challan-' . $challan->id);

        return Pdf::loadView('crm.design_jobs.delivery_challan', [
            'challan' => $challan,
            'print' => false,
        ])->setPaper('a4')->download($filename . '.pdf');
    }

    private function newChallanNo(): string
    {
        do {
            $no = 'DC-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -4));
        } while (DeliveryChallan::where('challan_no', $no)->exists());

        return $no;
    }
}
