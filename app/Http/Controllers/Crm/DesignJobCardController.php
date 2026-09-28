<?php

namespace App\Http\Controllers\Crm;

use App\DesignJob;
use App\DesignJobCard;
use App\Http\Controllers\Controller;
use App\Support\CrmWorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DesignJobCardController extends Controller
{
    /** Timed stages sharing the {prefix}_start / {prefix}_end / {prefix}_total_minutes convention. */
    private $timedStages = ['printing', 'lam', 'screen', 'foil', 'corr', 'die'];

    /** Single-value fields saved straight from the request. */
    private $scalarFields = [
        // Header
        'job_date', 'job_no', 'product', 'order_qty', 'priority_date',
        // Dummy / sample approval
        'dummy_sent_on', 'dummy_approved_on', 'dummy_approved_by',
        // Job briefing
        'box_l', 'box_w', 'box_h', 'box_unit', 'open_l', 'open_w', 'box_type', 'box_type_other',
        // Printing
        'printing_method', 'printing_method_other', 'ctp_plates', 'pms', 'coating_other_text',
        // Lamination
        'lam_other_text', 'lam_other_qty',
        // Screen printing / spot uv
        'screen_colors',
        // Foiling
        'foil_gold_shade', 'foil_silver_shade', 'foil_other_shade',
        // Corrugation
        'corr_color', 'corr_color_other', 'corr_ply',
        // Pasting
        'paste_other_text', 'paste_other_qty',
        // Quality check
        'qc_result', 'qc_comments', 'qc_approved_by',
        // Timeline
        'timeline_status', 'delay_days', 'delay_reason',
    ];

    private $booleanFields = [
        'priority_urgent', 'priority_critical', 'priority_substandard',
        'coating_uv', 'coating_coating', 'coating_varnish', 'coating_other',
        'lam_gloss', 'lam_matte', 'lam_soft_touch', 'lam_other',
        'screen_uv',
        'foil_gold', 'foil_silver', 'foil_other',
        'die_full', 'die_half', 'die_embossing', 'die_debossing',
        'paste_tape', 'paste_glue', 'paste_double', 'paste_pvc_window', 'paste_other',
    ];

    /** View access: designers, admins and sales (mirrors DesignJobController). */
    protected function requireAccess()
    {
        $user = Auth::guard('crm')->user();
        abort_unless($user, 403);
        if (!$user->isDesigner() && !$user->isAdmin() && !$user->isSales()) {
            abort(403, 'You do not have access to design jobs.');
        }
        return $user;
    }

    protected function findJob($id)
    {
        return DesignJob::where('workspace_id', CrmWorkspaceContext::id())->findOrFail($id);
    }

    public function edit($id)
    {
        $this->requireAccess();
        $job = $this->findJob($id);
        $card = $job->jobCard()->with(['materials', 'stocks'])->first()
            ?: new DesignJobCard(['design_job_id' => $job->id]);

        return view('crm.design_jobs.job_card', [
            'job' => $job,
            'card' => $card,
            'materials' => $card->exists ? $card->materials : collect(),
            'stocks' => $card->exists ? $card->stocks : collect(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->requireAccess();
        $job = $this->findJob($id);

        if (!$user->isAdmin() && !$user->isDesigner()) {
            abort(403, 'Only designers can edit the job card.');
        }

        $request->validate([
            'job_no' => 'nullable|string|max:255|unique:design_jobs,job_number,' . $job->id,
            'box_unit' => 'nullable|in:cm,inches,mm',
            'box_type' => 'nullable|in:hard,soft,other',
            'printing_method' => 'nullable|in:offset,digital,other',
            'corr_color' => 'nullable|in:brown,white,other',
            'qc_result' => 'nullable|in:approved,rejected',
            'timeline_status' => 'nullable|in:on_time,delayed',
            'qc_comments' => 'nullable|required_if:qc_result,rejected|string',
            'delay_days' => 'nullable|required_if:timeline_status,delayed|integer|min:0',
            'delay_reason' => 'nullable|required_if:timeline_status,delayed|string',
        ]);

        DB::transaction(function () use ($request, $job) {
            $card = $job->jobCard ?: new DesignJobCard();
            $card->design_job_id = $job->id;

            foreach ($this->scalarFields as $field) {
                $card->{$field} = $this->nullIfBlank($request->input($field));
            }
            foreach ($this->booleanFields as $field) {
                $card->{$field} = $request->boolean($field);
            }

            // Total plates: keep the submitted (auto) value, falling back to the CTP plate count.
            $card->total_plates = $this->nullIfBlank($request->input('total_plates'))
                ?? $this->nullIfBlank($request->input('ctp_plates'));

            foreach ($this->timedStages as $prefix) {
                $start = $this->nullIfBlank($request->input($prefix . '_start'));
                $end = $this->nullIfBlank($request->input($prefix . '_end'));
                $card->{$prefix . '_start'} = $start;
                $card->{$prefix . '_end'} = $end;
                $card->{$prefix . '_total_minutes'} = $this->minutesBetween($start, $end);
            }

            $card->save();

            // Sync the design job's title (from product) and job number (from Job No)
            // so the list reflects what the user typed in the card.
            $jobUpdates = [];
            $product = $this->nullIfBlank($request->input('product'));
            if ($product !== null && $product !== $job->title) {
                $jobUpdates['title'] = $product;
            }
            $jobNo = $this->nullIfBlank($request->input('job_no'));
            if ($jobNo !== null && $jobNo !== $job->job_number) {
                $jobUpdates['job_number'] = $jobNo;
            }
            if ($jobUpdates) {
                $job->update($jobUpdates);
            }

            $this->syncMaterials($card, $request->input('materials', []));
            $this->syncStocks($card, $request->input('stocks', []));
        });

        return redirect()
            ->route('crm.design_jobs.job_card.edit', $job->id)
            ->with('success', 'Job card saved.');
    }

    private function syncMaterials(DesignJobCard $card, array $rows)
    {
        $card->materials()->delete();
        $position = 0;
        foreach ($rows as $row) {
            if ($this->rowIsEmpty($row, ['item', 'specs', 'qty', 'needed_by', 'remarks'])) {
                continue;
            }
            $card->materials()->create([
                'position' => $position++,
                'item' => $this->nullIfBlank($row['item'] ?? null),
                'specs' => $this->nullIfBlank($row['specs'] ?? null),
                'qty' => $this->nullIfBlank($row['qty'] ?? null),
                'needed_by' => $this->nullIfBlank($row['needed_by'] ?? null),
                'remarks' => $this->nullIfBlank($row['remarks'] ?? null),
            ]);
        }
    }

    private function syncStocks(DesignJobCard $card, array $rows)
    {
        $card->stocks()->delete();
        $position = 0;
        foreach ($rows as $row) {
            $keys = ['material', 'gsm', 'sheet_l', 'sheet_w', 'sheet_qty', 'wastage', 'cutting_l', 'cutting_w', 'total_sheets'];
            if ($this->rowIsEmpty($row, $keys)) {
                continue;
            }
            $card->stocks()->create([
                'position' => $position++,
                'material' => $this->nullIfBlank($row['material'] ?? null),
                'gsm' => $this->nullIfBlank($row['gsm'] ?? null),
                'sheet_l' => $this->nullIfBlank($row['sheet_l'] ?? null),
                'sheet_w' => $this->nullIfBlank($row['sheet_w'] ?? null),
                'sheet_qty' => $this->nullIfBlank($row['sheet_qty'] ?? null),
                'wastage' => $this->nullIfBlank($row['wastage'] ?? null),
                'cutting_l' => $this->nullIfBlank($row['cutting_l'] ?? null),
                'cutting_w' => $this->nullIfBlank($row['cutting_w'] ?? null),
                'total_sheets' => $this->nullIfBlank($row['total_sheets'] ?? null),
            ]);
        }
    }

    private function rowIsEmpty($row, array $keys)
    {
        foreach ($keys as $key) {
            if ($this->nullIfBlank($row[$key] ?? null) !== null) {
                return false;
            }
        }
        return true;
    }

    private function nullIfBlank($value)
    {
        if ($value === null) {
            return null;
        }
        $value = is_string($value) ? trim($value) : $value;
        return $value === '' ? null : $value;
    }

    /** Minutes between two HH:MM times; wraps past midnight when end is earlier than start. */
    private function minutesBetween($start, $end)
    {
        if (!$start || !$end) {
            return null;
        }
        $s = $this->toMinutes($start);
        $e = $this->toMinutes($end);
        if ($s === null || $e === null) {
            return null;
        }
        $diff = $e - $s;
        if ($diff < 0) {
            $diff += 24 * 60;
        }
        return $diff;
    }

    private function toMinutes($time)
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            return null;
        }
        return ((int) $m[1]) * 60 + (int) $m[2];
    }
}
