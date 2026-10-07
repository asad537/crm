<?php

namespace App\Http\Controllers\Crm;

use App\CrmPrintReadyTicket;
use App\CrmUser;
use App\Http\Controllers\Controller;
use App\Mail\PrintReadyTicketMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Print Ready tab: tickets created when a paid order is sent to production.
 * Admin sees everything and grants designer access; designers pick tickets from the
 * Active pool, work them in My Tickets, and complete them into History (Proposal pattern).
 */
class PrintReadyController extends Controller
{
    private function guard()
    {
        $user = Auth::guard('crm')->user();
        if (!$user || !$user->canAccessPrintReady()) abort(403);
        return $user;
    }

    private function designers()
    {
        $workspaceId = session('crm_workspace_id') ?: \App\Support\CrmWorkspaceContext::id();
        return CrmUser::inWorkspace($workspaceId, ['designer'])->orderBy('name')
            ->get(['crm_users.id', 'crm_users.name', 'crm_users.email', 'crm_users.print_ready_access']);
    }

    public function index(Request $request)
    {
        $user = $this->guard();
        $isAdmin = $user->isAdmin();
        $tab = $request->query('tab', 'active');
        if (!array_key_exists($tab, CrmPrintReadyTicket::TAB_STATUSES)) $tab = 'active';

        // Admin sees all. Designer: Active = unassigned pool + own; My Tickets / History = own only.
        $scope = function ($q, $tabKey) use ($user, $isAdmin) {
            if ($isAdmin) return;
            if ($tabKey === 'active') {
                $q->where(function ($w) use ($user) { $w->whereNull('assigned_designer_id')->orWhere('assigned_designer_id', $user->id); });
            } else {
                $q->where('assigned_designer_id', $user->id);
            }
        };

        $tickets = CrmPrintReadyTicket::with(['designer', 'order'])
            ->whereIn('status', CrmPrintReadyTicket::TAB_STATUSES[$tab])
            ->where(function ($q) use ($scope, $tab) { $scope($q, $tab); })
            ->latest()->get();

        $counts = [];
        foreach (CrmPrintReadyTicket::TAB_STATUSES as $key => $statuses) {
            $counts[$key] = CrmPrintReadyTicket::whereIn('status', $statuses)->where(function ($q) use ($scope, $key) { $scope($q, $key); })->count();
        }

        return view('crm.print_ready.index', [
            'tickets' => $tickets, 'tab' => $tab, 'counts' => $counts,
            'isAdmin' => $isAdmin, 'currentUserId' => $user->id,
            'accessDesigners' => $isAdmin ? $this->designers() : collect(),
        ]);
    }

    public function show($id)
    {
        $user = $this->guard();
        $ticket = CrmPrintReadyTicket::with(['designer', 'order', 'brief', 'files.uploader', 'notes.user', 'creator'])->findOrFail($id);
        if (!$user->isAdmin() && $ticket->assigned_designer_id && (int) $ticket->assigned_designer_id !== (int) $user->id) {
            abort(403, 'This ticket is assigned to another designer.');
        }
        return view('crm.print_ready.show', [
            'ticket' => $ticket, 'isAdmin' => $user->isAdmin(), 'currentUserId' => $user->id,
            'isMine' => (int) $ticket->assigned_designer_id === (int) $user->id,
            'designers' => $user->isAdmin() ? $this->designers() : collect(),
        ]);
    }

    /** Designer picks a ticket from the Active pool. */
    public function claim($id)
    {
        $user = $this->guard();
        $ticket = CrmPrintReadyTicket::findOrFail($id);
        if ($ticket->assigned_designer_id && (int) $ticket->assigned_designer_id !== (int) $user->id && !$user->isAdmin()) {
            return redirect()->route('crm.print_ready.index')->with('error', 'This ticket has already been picked by another designer.');
        }
        if ($ticket->status === 'completed') {
            return redirect()->route('crm.print_ready.show', $ticket->id)->with('error', 'This ticket is already completed.');
        }
        $ticket->assigned_designer_id = $ticket->assigned_designer_id ?: $user->id;
        $ticket->status = 'in_progress';
        $ticket->claimed_at = $ticket->claimed_at ?: now();
        $ticket->save();
        $ticket->addNote($user->id, 'status', $user->name . ' picked this ticket · In Progress');
        return redirect()->route('crm.print_ready.show', $ticket->id)->with('success', 'Ticket ' . $ticket->ticket_number . ' is now in My Tickets.');
    }

    /** Designer / admin update: output path, note, files, and (admin) designer + status. Designers can complete. */
    public function update(Request $request, $id)
    {
        $user = $this->guard();
        $ticket = CrmPrintReadyTicket::findOrFail($id);
        $isAdmin = $user->isAdmin();
        if (!$isAdmin && (int) $ticket->assigned_designer_id !== (int) $user->id) abort(403);

        $data = $request->validate([
            'output_path' => 'nullable|string|max:500',
            'designer_note' => 'nullable|string|max:5000',
            'note' => 'nullable|string|max:5000',
            'assigned_designer_id' => 'nullable|integer',
            'status' => 'nullable|in:requested,in_progress,change_requested,completed',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|max:51200|mimes:pdf,ai,eps,psd,svg,png,jpg,jpeg,tif,tiff,zip,rar,7z,indd,cdr',
            'action' => 'nullable|in:save,complete',
        ], ['files.*.max' => 'Each file must be 50 MB or smaller.']);

        $ticket->output_path = $data['output_path'] ?? $ticket->output_path;
        $ticket->designer_note = $data['designer_note'] ?? $ticket->designer_note;

        if ($isAdmin) {
            if (array_key_exists('assigned_designer_id', $data)) {
                $newId = $data['assigned_designer_id'] ?: null;
                if ((int) $newId !== (int) $ticket->assigned_designer_id) {
                    $ticket->assigned_designer_id = $newId;
                    $name = $newId ? optional(CrmUser::find($newId))->name : null;
                    $ticket->addNote($user->id, 'status', $name ? "Assigned to {$name}" : 'Unassigned (back to Active pool)');
                    if (!$newId && $ticket->status !== 'completed') $ticket->status = 'requested';
                    elseif ($newId && $ticket->status === 'requested') $ticket->status = 'in_progress';
                }
            }
            if (!empty($data['status']) && $data['status'] !== $ticket->status) {
                $ticket->status = $data['status'];
                $ticket->addNote($user->id, 'status', 'Status changed to ' . $ticket->statusLabel());
            }
        }

        if (($data['action'] ?? 'save') === 'complete') {
            if (!$ticket->output_path && !$request->hasFile('files') && !$ticket->files()->exists()) {
                return back()->withInput()->with('error', 'Add the print-ready file(s) or the server path before completing the ticket.');
            }
            $ticket->status = 'completed';
            $ticket->completed_at = now();
            $ticket->addNote($user->id, 'status', $user->name . ' marked this ticket Completed');
        }

        // Files
        $uploaded = [];
        foreach ((array) $request->file('files', []) as $file) {
            if (!$file) continue;
            $name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $dir = public_path('crm_print_ready/' . $ticket->id);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $size = $file->getSize();
            $file->move($dir, $name);
            $ticket->files()->create(['path' => 'crm_print_ready/' . $ticket->id . '/' . $name, 'name' => $file->getClientOriginalName(), 'size' => $size, 'uploaded_by' => $user->id]);
            $uploaded[] = $file->getClientOriginalName();
        }
        if ($uploaded) $ticket->addNote($user->id, 'file', 'Uploaded: ' . implode(', ', $uploaded));
        if (!empty($data['note'])) $ticket->addNote($user->id, 'note', trim($data['note']));

        $ticket->save();

        if ($ticket->status === 'completed' && $ticket->wasChanged('status')) {
            $this->notify($ticket, 'completed', $user);
        }

        $msg = $ticket->status === 'completed' && $ticket->wasChanged('status') ? 'Ticket completed and moved to History.' : 'Ticket updated.';
        return redirect()->route('crm.print_ready.show', $ticket->id)->with('success', $msg);
    }

    /** Admin (or sales side) asks the designer for changes: back to My Tickets as Change Requested. */
    public function changeRequest(Request $request, $id)
    {
        $user = $this->guard();
        $ticket = CrmPrintReadyTicket::findOrFail($id);
        $data = $request->validate(['change_request_note' => 'required|string|max:5000']);
        $ticket->change_request_note = trim($data['change_request_note']);
        $ticket->status = 'change_requested';
        $ticket->completed_at = null;
        $ticket->save();
        $ticket->addNote($user->id, 'change_request', $ticket->change_request_note);
        $this->notify($ticket, 'change_requested', $user);
        return redirect()->route('crm.print_ready.show', $ticket->id)->with('success', 'Change request sent to the designer.');
    }

    public function download($id, $fileId)
    {
        $user = $this->guard();
        $ticket = CrmPrintReadyTicket::findOrFail($id);
        if (!$user->isAdmin() && $ticket->assigned_designer_id && (int) $ticket->assigned_designer_id !== (int) $user->id) abort(403);
        $file = $ticket->files()->findOrFail($fileId);
        $path = public_path($file->path);
        abort_unless(is_file($path), 404);
        return response()->download($path, $file->name);
    }

    public function grantAccess(Request $request)
    {
        $user = $this->guard();
        if (!$user->isAdmin()) abort(403);
        $data = $request->validate(['designer_id' => 'required|integer']);
        $designer = CrmUser::findOrFail($data['designer_id']);
        $designer->print_ready_access = 1;
        $designer->save();
        return redirect()->route('crm.print_ready.index')->with('success', 'Granted Print Ready access for ' . $designer->name . '.');
    }

    public function toggleAccess(Request $request, $designerId)
    {
        $user = $this->guard();
        if (!$user->isAdmin()) abort(403);
        $designer = CrmUser::findOrFail($designerId);
        $designer->print_ready_access = $request->boolean('grant') ? 1 : 0;
        $designer->save();
        return redirect()->route('crm.print_ready.index')->with('success', ($designer->print_ready_access ? 'Granted' : 'Revoked') . ' Print Ready access for ' . $designer->name . '.');
    }

    public function destroy($id)
    {
        $user = $this->guard();
        if (!$user->isAdmin()) abort(403);
        $ticket = CrmPrintReadyTicket::findOrFail($id);
        $ticket->notes()->delete();
        $ticket->files()->delete();
        $ticket->delete();
        return redirect()->route('crm.print_ready.index')->with('success', 'Ticket deleted.');
    }

    /** Email: created -> designers with access (or the assigned one); completed/change -> admins + designer. */
    public static function notify(CrmPrintReadyTicket $ticket, string $event, $actor = null): string
    {
        try {
            $ticket->loadMissing('designer');
            $admins = CrmUser::inWorkspace(null, ['admin'])->pluck('email')->filter()->all();
            if ($event === 'created') {
                $to = $ticket->designer ? [$ticket->designer->email]
                    : CrmUser::inWorkspace(null, ['designer'])->where('print_ready_access', 1)->pluck('email')->filter()->all();
                $cc = $admins;
            } elseif ($event === 'change_requested') {
                $to = $ticket->designer ? [$ticket->designer->email] : $admins; $cc = $ticket->designer ? $admins : [];
            } else {
                $to = $admins; $cc = $ticket->designer ? [$ticket->designer->email] : [];
            }
            $to = array_values(array_unique(array_filter($to)));
            $cc = array_values(array_diff(array_unique(array_filter($cc)), $to));
            if (!$to) return '';
            Mail::to($to)->cc($cc)->send(new PrintReadyTicketMail($ticket, $event, $actor));
            return ' Notified: ' . implode(', ', $to) . '.';
        } catch (\Throwable $e) {
            Log::warning('Print Ready mail failed (' . $event . ') for ticket #' . $ticket->id . ': ' . $e->getMessage());
            return '';
        }
    }
}
