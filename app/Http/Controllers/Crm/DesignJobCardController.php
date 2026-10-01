<?php

namespace App\Http\Controllers\Crm;

use App\DesignJob;
use App\DesignJobCard;
use App\Http\Controllers\Controller;
use App\Support\CrmWorkspaceContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DesignJobCardController extends Controller
{
    private const OPTIONAL_SECTIONS = [
        'briefing', 'stock', 'foam', 'printing', 'lamination',
        'screen', 'foiling', 'corrugation', 'diecutting', 'pasting', 'quality', 'timeline', 'attachments',
    ];

    /** Single-value fields saved straight from the request. */
    private $scalarFields = [
        // Header
        'job_date', 'job_no', 'product', 'order_qty', 'job_start_on',
        // Dummy / sample approval
        'dummy_sent_on', 'dummy_approved_on', 'dummy_approved_by',
        // Job briefing
        'box_l', 'box_w', 'box_h', 'box_unit', 'open_l', 'open_w', 'box_type', 'box_type_other',
        // Foam
        'foam_type', 'foam_type_other', 'foam_color', 'foam_color_other', 'foam_thickness', 'foam_qty',
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
        'qc_result', 'qc_comments', 'qc_rejection_comments', 'qc_approved_by',
        // Timeline
        'timeline_status', 'delay_reason',
    ];

    private $booleanFields = [
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
        $card = $job->jobCard()->with('stocks', 'attachments')->first()
            ?: new DesignJobCard(['design_job_id' => $job->id]);

        return view('crm.design_jobs.job_card', [
            'job' => $job,
            'card' => $card,
            'stocks' => $card->exists ? $card->stocks : collect(),
            'attachments' => $card->exists ? $card->attachments : collect(),
        ]);
    }

    public function downloadAttachment($id, $attachmentId)
    {
        $this->requireAccess();
        $job = $this->findJob($id);
        $card = $job->jobCard;
        abort_unless($card, 404);
        $attachment = $card->attachments()->findOrFail($attachmentId);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);

        return response()->download($disk->path($attachment->path), $attachment->original_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf($id)
    {
        $this->requireAccess();
        $job = $this->findJob($id);
        $card = $job->jobCard()->with('stocks')->first()
            ?: new DesignJobCard(['design_job_id' => $job->id]);

        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', $job->job_number ?: 'job-' . $job->id);

        return Pdf::loadView('crm.design_jobs.job_card_pdf', [
            'job' => $job,
            'card' => $card,
            'stocks' => $card->exists ? $card->stocks : collect(),
        ])->setPaper('a4')->setOption('enable_font_subsetting', true)
            ->download($filename . '-job-card.pdf');
    }

    /** On-screen job card (same layout as the PDF) that opens the print dialog. */
    public function print($id)
    {
        $this->requireAccess();
        $job = $this->findJob($id);
        $card = $job->jobCard()->with('stocks')->first()
            ?: new DesignJobCard(['design_job_id' => $job->id]);

        return view('crm.design_jobs.job_card_pdf', [
            'job' => $job,
            'card' => $card,
            'stocks' => $card->exists ? $card->stocks : collect(),
            'print' => true,
        ]);
    }

    /** Dummy / Sample Approval — a separate step done after the job is saved. */
    public function dummy($id)
    {
        $this->requireAccess();
        $job = $this->findJob($id);
        $card = $job->jobCard ?: new DesignJobCard(['design_job_id' => $job->id]);

        return view('crm.design_jobs.dummy', [
            'job' => $job,
            'card' => $card,
        ]);
    }

    public function saveDummy(Request $request, $id)
    {
        $user = $this->requireAccess();
        $job = $this->findJob($id);
        abort_unless($user->isAdmin() || $user->isDesigner(), 403, 'Only designers can record the dummy.');

        $data = $request->validate([
            'dummy_sent_on' => 'nullable|date',
            'dummy_approved_on' => 'nullable|date',
            'dummy_approved_by' => 'nullable|string|max:255',
        ]);

        $card = $job->jobCard ?: new DesignJobCard(['design_job_id' => $job->id]);
        if (!$card->exists) {
            $card->job_no = $job->job_number;
            $card->job_date = optional($job->created_at)->toDateString() ?: now()->toDateString();
        }
        $card->dummy_sent_on = $this->nullIfBlank($data['dummy_sent_on'] ?? null);
        $card->dummy_approved_on = $this->nullIfBlank($data['dummy_approved_on'] ?? null);
        $card->dummy_approved_by = $this->nullIfBlank($data['dummy_approved_by'] ?? null);
        $card->save();

        return redirect()->route('crm.design_jobs.index')
            ->with('success', 'Dummy saved for ' . $job->job_number . '.');
    }

    public function store(Request $request)
    {
        $user = $this->requireAccess();
        abort_unless($user->isAdmin() || $user->isDesigner(), 403, 'Only designers can create job cards.');

        $draft = $request->input('save_mode') === 'draft';
        $this->validateCard($request, null, $draft);
        $request->validate(['product' => ($draft ? 'nullable' : 'required') . '|string|max:255']);

        $uploadedPaths = [];
        $removedPaths = [];
        try {
            $job = DB::transaction(function () use ($request, $user, $draft, &$uploadedPaths, &$removedPaths) {
                $jobNumber = $this->nullIfBlank($request->input('job_no')) ?: $this->newJobNumber();
                $job = DesignJob::create([
                    'job_number' => $jobNumber,
                    'workspace_id' => CrmWorkspaceContext::id(),
                    'designer_id' => $user->id,
                    'title' => $this->nullIfBlank($request->input('product')) ?: 'Untitled Job',
                    'estimate_number' => $this->nullIfBlank($request->input('estimate_number')),
                    'estimated_delivery_date' => $this->nullIfBlank($request->input('estimated_delivery_date')),
                    'due_date' => $this->nullIfBlank($request->input('due_date')),
                    'status' => 'designing',
                    'status_updated_at' => now(),
                ]);
                $card = $this->saveCard($request, $job, $draft);
                $this->syncAttachments($request, $card, $uploadedPaths, $removedPaths);

                return $job;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($uploadedPaths);
            throw $e;
        }

        return redirect()->route('crm.design_jobs.index')
            ->with('success', 'Job ' . $job->job_number . ' saved.');
    }

    public function update(Request $request, $id)
    {
        $user = $this->requireAccess();
        $job = $this->findJob($id);

        if (!$user->isAdmin() && !$user->isDesigner()) {
            abort(403, 'Only designers can edit the job card.');
        }

        $draft = $request->input('save_mode') === 'draft';
        $this->validateCard($request, $job->id, $draft);

        $uploadedPaths = [];
        $removedPaths = [];
        try {
            DB::transaction(function () use ($request, $job, $draft, &$uploadedPaths, &$removedPaths) {
                $card = $this->saveCard($request, $job, $draft);
                $this->syncAttachments($request, $card, $uploadedPaths, $removedPaths);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($uploadedPaths);
            throw $e;
        }
        Storage::disk('local')->delete($removedPaths);

        return redirect()->route('crm.design_jobs.index')
            ->with('success', 'Job ' . $job->job_number . ' saved.');
    }

    private function validateCard(Request $request, $jobId, $draft = false)
    {
        $rules = [
            'job_no' => 'nullable|string|max:255|unique:design_jobs,job_number' . ($jobId ? ',' . $jobId : ''),
            'job_date' => 'nullable|date',
            'job_start_on' => 'nullable|date',
            'dummy_sent_on' => 'nullable|date',
            'dummy_approved_on' => 'nullable|date',
            'dummy_approved_by' => 'nullable|string|max:255',
            'priority_level' => ($draft ? 'nullable' : 'required') . '|in:regular,urgent,critical',
            'estimate_number' => 'nullable|string|max:255',
            'estimated_delivery_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'box_unit' => 'nullable|in:cm,inches,mm',
            'box_type' => 'nullable|in:hard,soft,other',
            'foam_type' => 'nullable|in:eva,soft,other',
            'foam_type_other' => 'nullable|string|max:255',
            'foam_color' => 'nullable|in:black,white,other',
            'foam_color_other' => 'nullable|string|max:255',
            'foam_thickness' => 'nullable|numeric|min:0',
            'foam_qty' => 'nullable|integer|min:0',
            'printing_method' => 'nullable|in:offset,digital,other',
            'corr_color' => 'nullable|in:brown,white,other',
            'qc_result' => 'nullable|in:approved,rejected',
            'timeline_status' => 'nullable|in:on_time,delayed',
            'qc_comments' => 'nullable|string',
            'qc_rejection_comments' => $draft ? 'nullable|string' : 'nullable|required_if:qc_result,rejected|string',
            'delay_reason' => 'nullable|string',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|extensions:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,ai,psd,eps,zip|max:20480',
            'remove_attachments' => 'nullable|array|max:50',
            'remove_attachments.*' => 'integer|distinct',
            'wizard_completed_step' => 'nullable|integer|between:-1,14',
            'wizard_current_step' => 'nullable|integer|between:0,14',
        ];
        // Dummy / Sample Approval is fully optional — no required fields.
        $request->validate($rules);

        $totalUploadBytes = array_sum(array_map(function ($file) {
            return $file->getSize();
        }, $request->file('attachments', [])));
        if ($totalUploadBytes > 40 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'attachments' => 'Attachments must be 40 MB or less in total per save.',
            ]);
        }
    }

    private function saveCard(Request $request, DesignJob $job, $draft = false)
    {
        $card = $job->jobCard ?: new DesignJobCard();
        $card->design_job_id = $job->id;

        foreach ($this->scalarFields as $field) {
            $card->{$field} = $this->nullIfBlank($request->input($field));
        }
        if (!$card->job_date) {
            $card->job_date = optional($job->created_at)->toDateString() ?: now()->toDateString();
        }
        $choices = array_fill_keys(self::OPTIONAL_SECTIONS, 'yes');
        $choices['dummy'] = 'yes';
        $choices['__draft'] = $draft;
        $choices['__completed_step'] = $draft ? (int) $request->input('wizard_completed_step', -1) : 14;
        $choices['__active_step'] = (int) $request->input('wizard_current_step', 0);
        $card->section_choices = $choices;
        $card->job_no = $card->job_no ?: $job->job_number;
        foreach ($this->booleanFields as $field) {
            $card->{$field} = $request->boolean($field);
        }
        $card->priority_urgent = $request->input('priority_level') === 'urgent';
        $card->priority_critical = $request->input('priority_level') === 'critical';

        // Total plates: keep the submitted (auto) value, falling back to the CTP plate count.
        $card->total_plates = $this->nullIfBlank($request->input('total_plates'))
            ?? $this->nullIfBlank($request->input('ctp_plates'));

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
        foreach (['estimate_number', 'estimated_delivery_date', 'due_date'] as $field) {
            if ($request->has($field)) {
                $jobUpdates[$field] = $this->nullIfBlank($request->input($field));
            }
        }
        if ($jobUpdates) {
            $job->update($jobUpdates);
        }

        // Procurement is a separate paper/form; retain any previously saved rows.
        $this->syncStocks($card, $request->input('stocks', []));

        return $card;
    }

    private function syncAttachments(Request $request, DesignJobCard $card, array &$uploadedPaths, array &$removedPaths)
    {
        $removeIds = $request->input('remove_attachments', []);
        if ($removeIds) {
            foreach ($card->attachments()->whereIn('id', $removeIds)->get() as $attachment) {
                $removedPaths[] = $attachment->path;
                $attachment->delete();
            }
        }

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('design-job-cards/' . $card->id, 'local');
            if (!$path) {
                throw new \RuntimeException('Could not store the job attachment.');
            }
            $uploadedPaths[] = $path;
            $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
            $card->attachments()->create([
                'path' => $path,
                'original_name' => mb_substr($name, 0, 255),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function newJobNumber()
    {
        do {
            $jobNumber = 'JOB-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        } while (DesignJob::where('job_number', $jobNumber)->exists());

        return $jobNumber;
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

}
