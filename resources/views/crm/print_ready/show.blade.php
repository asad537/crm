@extends('crm.layout')
@section('title', 'Print Ready ' . $ticket->ticket_number)

@section('content')
@php
    $fields = \App\CrmManualOrderProductionBrief::PRODUCT_FIELDS;
    $order = $ticket->order;
    $canWork = $isAdmin || ($isMine && $ticket->status !== 'completed');
    $fmt = fn ($x) => $x ? $x->format('d M Y') : '—';
    $late = $ticket->printers_deadline && $ticket->status !== 'completed' && $ticket->printers_deadline->isPast();
@endphp
<style>
.pr-top{display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin-bottom:1rem;flex-wrap:wrap}
.pr-back{display:inline-flex;align-items:center;gap:.4rem;color:#64748b;text-decoration:none;font-weight:700;font-size:.85rem}
.pr-grid2{display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:1.1rem;align-items:start}
.pr-card{background:#fff;border:1px solid #e4eaf1;border-radius:16px;box-shadow:0 8px 26px rgba(15,23,42,.05);padding:1.3rem 1.4rem;margin-bottom:1.1rem}
.pr-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.pr-title{margin:0;font-size:1.2rem;font-weight:850;color:#0f172a}
.pr-eyebrow{font-size:.63rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--primary-purple)}
.pr-status{display:inline-flex;padding:.34rem .7rem;border-radius:999px;font-size:.66rem;font-weight:850;text-transform:uppercase;letter-spacing:.03em}
.pr-status.requested{background:#eef2ff;color:#4338ca}.pr-status.in_progress{background:#eaf2ff;color:#285fbd}.pr-status.change_requested{background:#fff3e8;color:#c2410c}.pr-status.completed{background:#eafbf2;color:#08784c}
.pr-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem 1rem}
.pr-k{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#8795a7}
.pr-v{font-size:.84rem;color:#0f172a;margin-top:.12rem;white-space:pre-wrap;word-break:break-word}
.pr-v:empty::before{content:'—';color:#cbd5e1}
.pr-v.late{color:#b91c1c;font-weight:800}
.pr-full{grid-column:1/-1}
.pr-prod{border:1px solid #e4eaf1;border-radius:11px;padding:.8rem .9rem;margin-top:.7rem;background:#fcfcfd}
.pr-prod h4{margin:0 0 .5rem;font-size:.86rem;color:var(--primary-purple)}
.pr-field{display:flex;flex-direction:column;gap:.3rem;margin-bottom:.8rem}
.pr-label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#718096}
.pr-input,.pr-textarea,.pr-select{width:100%;padding:.6rem .7rem;border:1.5px solid #dbe3ec;border-radius:9px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.85rem}
.pr-input:focus,.pr-textarea:focus,.pr-select:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pr-textarea{min-height:80px;resize:vertical}
.pr-actions{display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.6rem}
.pr-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem 1rem;border-radius:10px;border:1px solid #dbe3ec;background:#fff;color:#475569;font-weight:800;font-size:.85rem;cursor:pointer;text-decoration:none}
.pr-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
.pr-btn.ok{background:#16a34a;color:#fff;border-color:#16a34a}
.pr-btn.warn{background:#fff7ed;color:#c2410c;border-color:#fed7aa}
.pr-btn.danger{color:#dc2626;border-color:#fecaca}
.pr-note{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:.9rem 1rem;color:#7c2d12;font-size:.85rem}
.pr-files{display:flex;flex-direction:column;gap:.4rem}
.pr-file{display:flex;align-items:center;gap:.6rem;padding:.55rem .7rem;border:1px solid #e4eaf1;border-radius:9px;font-size:.8rem}
.pr-file i{color:var(--primary-purple)}
.pr-file a{color:#0f172a;font-weight:700;text-decoration:none;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pr-file small{color:#94a3b8;white-space:nowrap}
.pr-log{display:flex;flex-direction:column;gap:.5rem}
.pr-log-row{display:flex;gap:.6rem;font-size:.8rem;padding:.5rem .6rem;border-left:3px solid #e4eaf1;background:#f8fafc;border-radius:0 8px 8px 0}
.pr-log-row.status{border-left-color:var(--primary-purple)}.pr-log-row.change_request{border-left-color:#f97316}.pr-log-row.file{border-left-color:#16a34a}
.pr-log-row .who{font-weight:800;color:#1f2b3d;white-space:nowrap}.pr-log-row .when{color:#94a3b8;white-space:nowrap;margin-left:auto;font-size:.72rem}
.pr-chips{display:flex;gap:.4rem;flex-wrap:wrap}
.pr-chip{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .6rem;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.68rem;font-weight:800}
.pr-sticky{position:sticky;top:1rem}
@media(max-width:1100px){.pr-grid2{grid-template-columns:1fr}.pr-sticky{position:static}.pr-grid{grid-template-columns:1fr 1fr}}
</style>

<div class="pr-top">
    <a href="{{ route('crm.print_ready.index') }}" class="pr-back"><i class="fas fa-arrow-left"></i> Back to Print Ready</a>
    <div class="pr-chips">
        @if($order)<a class="pr-chip" href="{{ route('crm.orders.manual.production.pdf', $order->id) }}" target="_blank" style="text-decoration:none"><i class="fas fa-file-pdf"></i> Production Brief PDF</a>@endif
        @if($order && $isAdmin)<a class="pr-chip" href="{{ route('crm.orders.manual.production.show', $order->id) }}" style="text-decoration:none"><i class="fas fa-industry"></i> Production Job</a>@endif
    </div>
</div>

<div class="pr-grid2">
    <div>
        <div class="pr-card">
            <div class="pr-head">
                <div><div class="pr-eyebrow">Print Ready Ticket {{ $ticket->ticket_number }}</div><h1 class="pr-title">Job {{ $ticket->job_number }} · {{ $ticket->client_name }}</h1></div>
                <span class="pr-status {{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
            </div>
            <div class="pr-grid">
                <div><div class="pr-k">Designer</div><div class="pr-v">{{ optional($ticket->designer)->name ?: 'Not assigned' }}</div></div>
                <div><div class="pr-k">Printer's Deadline</div><div class="pr-v {{ $late ? 'late' : '' }}">{{ $fmt($ticket->printers_deadline) }}{{ $late ? ' · overdue' : '' }}</div></div>
                <div><div class="pr-k">Client's Deadline</div><div class="pr-v">{{ $fmt($ticket->clients_deadline) }}</div></div>
                <div><div class="pr-k">Created</div><div class="pr-v">{{ $ticket->created_at->format('d M Y H:i') }}{{ $ticket->creator ? ' · '.$ticket->creator->name : '' }}</div></div>
                <div class="pr-full"><div class="pr-k">Folder Path (source files)</div><div class="pr-v">{{ $ticket->folder_path }}</div></div>
                @if($ticket->brief_notes)<div class="pr-full"><div class="pr-k">Notes from production brief</div><div class="pr-v">{{ $ticket->brief_notes }}</div></div>@endif
                @if($ticket->brief && $ticket->brief->production_type)<div><div class="pr-k">Production Type</div><div class="pr-v">{{ $ticket->brief->productionTypeLabel() }}</div></div><div><div class="pr-k">Job Type</div><div class="pr-v">{{ $ticket->brief->job_type }}</div></div>@endif
            </div>
            @foreach(($ticket->products ?: []) as $i => $pr)
                <div class="pr-prod">
                    <h4>Product {{ $i + 1 }}: {{ $pr['product'] ?? '' }}</h4>
                    <div class="pr-grid">
                        @foreach($fields as $k => $lbl)@if($k === 'product')@continue @endif
                            <div class="{{ $k === 'additional_requirements' ? 'pr-full' : '' }}"><div class="pr-k">{{ $lbl }}</div><div class="pr-v">{{ $pr[$k] ?? '' }}</div></div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @if($ticket->change_request_note && $ticket->status === 'change_requested')
            <div class="pr-card"><div class="pr-eyebrow" style="margin-bottom:.5rem">Change requested</div><div class="pr-note">{{ $ticket->change_request_note }}</div></div>
        @endif

        <div class="pr-card">
            <div class="pr-eyebrow" style="margin-bottom:.6rem">Activity</div>
            <div class="pr-log">
                @forelse($ticket->notes as $n)
                    <div class="pr-log-row {{ $n->type }}"><span class="who">{{ optional($n->user)->name ?: 'System' }}</span><span style="white-space:pre-wrap">{{ $n->body }}</span><span class="when">{{ $n->created_at->format('d M, H:i') }}</span></div>
                @empty
                    <div class="pr-v" style="color:#94a3b8">No activity yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="pr-sticky">
        <div class="pr-card">
            <div class="pr-eyebrow" style="margin-bottom:.6rem">Print-ready files</div>
            <div class="pr-files">
                @forelse($ticket->files as $f)
                    <div class="pr-file"><i class="fas fa-file-alt"></i><a href="{{ route('crm.print_ready.download', [$ticket->id, $f->id]) }}">{{ $f->name }}</a><small>{{ $f->size ? number_format($f->size / 1048576, 1) . ' MB' : '' }} · {{ optional($f->uploader)->name }}</small></div>
                @empty
                    <div class="pr-v" style="color:#94a3b8">No files uploaded yet.</div>
                @endforelse
            </div>
            @if($ticket->output_path)<div style="margin-top:.7rem"><div class="pr-k">Print-ready server path</div><div class="pr-v">{{ $ticket->output_path }}</div></div>@endif
        </div>

        @if($ticket->status === 'requested' && !$ticket->assigned_designer_id && !$isAdmin)
            <div class="pr-card">
                <form method="POST" action="{{ route('crm.print_ready.claim', $ticket->id) }}">{{ csrf_field() }}
                    <p style="margin:0 0 .7rem;font-size:.84rem;color:#475569">This ticket is in the Active pool. Pick it to start working on it.</p>
                    <button type="submit" class="pr-btn primary" style="width:100%;justify-content:center"><i class="fas fa-hand-paper"></i> Pick this ticket</button>
                </form>
            </div>
        @endif

        @if($canWork)
            <form method="POST" action="{{ route('crm.print_ready.update', $ticket->id) }}" enctype="multipart/form-data" class="pr-card" id="prForm">
                {{ csrf_field() }}
                <input type="hidden" name="action" id="prAction" value="save">
                <div class="pr-eyebrow" style="margin-bottom:.6rem">{{ $isAdmin ? 'Manage ticket' : 'Your work' }}</div>
                @if($isAdmin)
                    <div class="pr-field"><span class="pr-label">Assigned designer</span>
                        <select class="pr-select" name="assigned_designer_id">
                            <option value="">— Active pool (not assigned) —</option>
                            @foreach($designers as $d)<option value="{{ $d->id }}" {{ (int)$ticket->assigned_designer_id === (int)$d->id ? 'selected' : '' }}>{{ $d->name }}{{ $d->print_ready_access ? '' : ' (no access)' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="pr-field"><span class="pr-label">Status</span>
                        <select class="pr-select" name="status">
                            @foreach(\App\CrmPrintReadyTicket::STATUSES as $val => $lbl)<option value="{{ $val }}" {{ $ticket->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <div class="pr-field"><span class="pr-label">Upload print-ready file(s)</span><input class="pr-input" type="file" name="files[]" multiple accept=".pdf,.ai,.eps,.psd,.svg,.png,.jpg,.jpeg,.tif,.tiff,.zip,.rar,.7z,.indd,.cdr"><small style="color:#94a3b8;font-size:.7rem">PDF, AI, EPS, PSD, TIFF, ZIP… up to 50 MB each.</small></div>
                <div class="pr-field"><span class="pr-label">Print-ready server path</span><input class="pr-input" name="output_path" value="{{ old('output_path', $ticket->output_path) }}" placeholder="\\server\print-ready\TCB-0001\final.pdf"></div>
                <div class="pr-field"><span class="pr-label">Designer note</span><textarea class="pr-textarea" name="designer_note" placeholder="Specs used, bleed, dieline version, anything production should know...">{{ old('designer_note', $ticket->designer_note) }}</textarea></div>
                <div class="pr-field"><span class="pr-label">Add activity note</span><input class="pr-input" name="note" placeholder="Optional short update (goes to the activity log)"></div>
                <div class="pr-actions">
                    <button class="pr-btn primary" type="submit"><i class="fas fa-save"></i> Save</button>
                    @if($ticket->status !== 'completed')
                        <button class="pr-btn ok" type="submit" onclick="document.getElementById('prAction').value='complete';return confirm('Mark this ticket as completed? It will move to History.')"><i class="fas fa-check-circle"></i> Mark Completed</button>
                    @endif
                </div>
            </form>
        @endif

        @if($isAdmin || $isMine)
            <form method="POST" action="{{ route('crm.print_ready.change_request', $ticket->id) }}" class="pr-card">
                {{ csrf_field() }}
                <div class="pr-eyebrow" style="margin-bottom:.5rem">Request a change</div>
                <div class="pr-field"><textarea class="pr-textarea" name="change_request_note" placeholder="Describe what needs to change in the artwork..." required></textarea></div>
                <div class="pr-actions">
                    <button class="pr-btn warn" type="submit"><i class="fas fa-undo-alt"></i> Submit Change Request</button>
                    @if($isAdmin)<button class="pr-btn danger" type="submit" form="prDeleteForm" onclick="return confirm('Delete this ticket?')"><i class="fas fa-trash-alt"></i> Delete</button>@endif
                </div>
            </form>
            @if($isAdmin)<form id="prDeleteForm" method="POST" action="{{ route('crm.print_ready.destroy', $ticket->id) }}" style="display:none">{{ csrf_field() }}<input type="hidden" name="_method" value="DELETE"></form>@endif
        @endif
    </div>
</div>
@endsection
