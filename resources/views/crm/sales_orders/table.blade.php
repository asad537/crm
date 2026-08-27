<table class="table" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="border-bottom: 2px solid #f1f5f9; text-align: left;">
            <th style="padding: 12px;">ID</th>
            <th style="padding: 12px;">Client</th>
            <th style="padding: 12px;">Product</th>
            <th style="padding: 12px;">Payment Terms</th>
            <th style="padding: 12px;">Payment Status</th>
            <th style="padding: 12px;">Order Status</th>
            <th style="padding: 12px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($salesOrders as $order)
            <tr style="border-bottom: 1px solid #f8fafc;">
                <td style="padding: 12px;">#{{ $order->id }}</td>
                <td style="padding: 12px;">
                    <div style="font-weight: 600;">{{ $order->lead->client_name ?? 'N/A' }}</div>
                    <div style="font-size: 0.8rem; color: #64748b;">{{ $order->lead->client_email ?? '' }}</div>
                </td>
                <td style="padding: 12px;">{{ $order->lead->product_name ?? 'N/A' }}</td>
                <td style="padding: 12px;">
                    @if($order->payment_term == 'credit')
                        Net {{ $order->credit_days }}
                    @elseif($order->payment_term == '100_deposit')
                        100% Deposit
                    @else
                        50% Advance
                    @endif
                </td>
                <td style="padding: 12px;">
                    @if($order->payment_status == 'pending')
                        <span style="background: #fef08a; color: #854d0e; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">Pending</span>
                    @elseif($order->payment_status == 'approved')
                        <span style="background: #bfdbfe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">Credit Approved</span>
                    @else
                        <span style="background: #bbf7d0; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">Received</span>
                    @endif
                </td>
                <td style="padding: 12px;">
                    @if($order->status == 'pending_payment')
                        <span style="background: #fef08a; color: #854d0e; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">Awaiting Payment</span>
                    @elseif($order->status == 'pending_artwork')
                        <span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">Needs Artwork</span>
                    @elseif($order->status == 'in_design')
                        <span style="background: #bfdbfe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">In Design</span>
                    @elseif($order->status == 'design_approved')
                        <span style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">Awaiting Client Approval</span>
                    @elseif($order->status == 'prepress')
                        <span style="background: #dbeafe; color: #1d4ed8; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">In Prepress</span>
                    @elseif($order->status == 'in_production')
                        <span style="background: var(--primary-soft); color: var(--primary-purple); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">In Production</span>
                    @else
                        <span style="background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; display: inline-block; text-align: center;">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                    @endif
                </td>
                <td style="padding: 12px;">
                    <div style="display: flex; gap: 5px; flex-wrap: wrap; align-items: center;">
                        @if($order->payment_status == 'pending')
                            @if(\Auth::guard('crm')->user()->isAccounts() || \Auth::guard('crm')->user()->isAdmin())
                            <form action="{{ route('crm.sales_orders.update_payment_status', $order->id) }}" method="POST" style="margin: 0;">
                                {{ csrf_field() }}
                                {{ method_field('PATCH') }}
                                <button type="submit" style="background: #16a34a; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                    Mark Paid/Approved
                                </button>
                            </form>
                            @else
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">Awaiting Account Approval</span>
                            @endif
                        @elseif($order->status == 'pending_artwork')
                            <button onclick="document.getElementById('artworkModal{{ $order->id }}').style.display='flex'" style="background: var(--primary-purple); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                Upload Artwork
                            </button>

                            <!-- Upload Artwork Modal -->
                            <div id="artworkModal{{ $order->id }}" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
                                <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Upload Artwork & Create Design Ticket</h3>
                                        <i class="fas fa-times" style="cursor: pointer; color: #94a3b8; font-size: 1.2rem;" onclick="document.getElementById('artworkModal{{ $order->id }}').style.display='none'"></i>
                                    </div>
                                    <form id="artworkForm{{ $order->id }}" action="{{ route('crm.sales_orders.upload_artwork', $order->id) }}" method="POST" enctype="multipart/form-data" onsubmit="uploadArtworkWithProgress(event, this, {{ $order->id }})">
                                        {{ csrf_field() }}
                                        <div style="margin-bottom: 1rem;">
                                            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Artwork File (.zip, .ai, .pdf, .jpg, .png)</label>
                                            <input type="file" name="artwork_file" accept=".zip,.ai,.pdf,.jpg,.jpeg,.png,.eps" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                        </div>
                                        <div style="margin-bottom: 1rem;">
                                            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Notes for Designer</label>
                                            <textarea name="design_notes" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none;" placeholder="e.g. Please put logo on the front panel..."></textarea>
                                        </div>

                                        <div id="progress-container-{{ $order->id }}" style="display: none; margin-bottom: 1rem;">
                                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 600; color: #475569;">
                                                <span>Uploading...</span>
                                                <span id="progress-text-{{ $order->id }}">0%</span>
                                            </div>
                                            <div style="width: 100%; background-color: #e2e8f0; border-radius: 999px; height: 8px; overflow: hidden;">
                                                <div id="progress-bar-{{ $order->id }}" style="width: 0%; height: 100%; background-color: var(--primary-purple); transition: width 0.2s;"></div>
                                            </div>
                                        </div>

                                        <button type="submit" id="submitBtn{{ $order->id }}" style="width: 100%; padding: 0.85rem; background: var(--primary-purple); color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 1rem; cursor: pointer;">
                                            Send to Design Department
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                        @if($order->status == 'design_approved')
                            @php
                                $latestProof = \App\ProofRevision::where('crm_email_id', $order->crm_email_id)->orderBy('version_number', 'desc')->first();
                            @endphp
                            @if($latestProof)
                                <a href="{{ asset($latestProof->file_path) }}" target="_blank" style="background: #10b981; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; text-decoration: none; display: inline-block;">
                                    <i class="fas fa-file-pdf"></i> View Proof
                                </a>
                                <a href="{{ route('crm.emails.show', $order->crm_email_id) }}?action=send_proof" style="background: #0ea5e9; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; text-decoration: none; display: inline-block;">
                                    <i class="fas fa-paper-plane"></i> Send to Client
                                </a>
                                <form action="{{ route('crm.sales_orders.approve_proof', $order->id) }}" method="POST" style="margin: 0;">
                                    {{ csrf_field() }}
                                    <button type="submit" style="background: var(--primary-purple); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <button type="button" onclick="document.getElementById('rejectProofModal{{ $order->id }}').style.display='flex'" style="background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                    <i class="fas fa-undo"></i> Request Changes
                                </button>

                                <!-- Request Changes Modal -->
                                <div id="rejectProofModal{{ $order->id }}" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
                                    <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Request Design Changes</h3>
                                            <i class="fas fa-times" style="cursor: pointer; color: #94a3b8; font-size: 1.2rem;" onclick="document.getElementById('rejectProofModal{{ $order->id }}').style.display='none'"></i>
                                        </div>
                                         <form action="{{ route('crm.sales_orders.reject_proof', $order->id) }}" method="POST" enctype="multipart/form-data">
                                             {{ csrf_field() }}
                                             <div style="margin-bottom: 1rem;">
                                                 <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Revision Notes for Designer</label>
                                                 <textarea name="revision_notes" rows="3" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none;" placeholder="e.g. Please make the logo larger..."></textarea>
                                             </div>

                                             <div style="margin-bottom: 1.5rem;">
                                                 <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Attach Reference File <span style="color:#94a3b8; font-weight:500;">(Optional)</span></label>
                                                 <input type="file" name="revision_attachment" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 8px;" accept=".jpg,.jpeg,.png,.gif,.pdf,.ai,.eps,.psd,.svg,.zip,.doc,.docx">
                                                 @if($order->sales_revision_attachment)
                                                 <div style="display:flex; align-items:center; gap:8px; margin-top:8px; padding:9px 12px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:9px;">
                                                     <i class="fas fa-paperclip" style="color:#475569; font-size:0.85rem;"></i>
                                                     <span style="font-size:0.78rem; color:#475569; font-weight:700;">Previous attachment:</span>
                                                     <a href="{{ asset($order->sales_revision_attachment) }}" target="_blank"
                                                        style="font-size:0.78rem; color:var(--primary-purple); font-weight:700; text-decoration:none; word-break:break-all;">
                                                         {{ basename($order->sales_revision_attachment) }}
                                                     </a>
                                                 </div>
                                                 @endif
                                             </div>

                                             <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-purple); color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 1rem; cursor: pointer;">
                                                 Send Back to Design
                                             </button>
                                         </form>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($order->productionJob && $order->productionJob->status === 'sales_agent_review')
                            @php
                                $currentUser = Auth::guard('crm')->user();
                                $isAgent = (int)$order->sales_agent_id === (int)$currentUser->id;
                            @endphp
                            @if($currentUser->isAdmin() || $isAgent)
                                <form action="{{ route('crm.production_jobs.sales_agent_review', $order->productionJob->id) }}" method="POST" style="margin: 0; display: inline-block;">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" style="background: var(--primary-purple); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                        <i class="fas fa-check"></i> Approve Print
                                    </button>
                                </form>
                                <button type="button" onclick="document.getElementById('rejectPrintModal{{ $order->id }}').style.display='flex'" style="background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; display: inline-block;">
                                    <i class="fas fa-undo"></i> Request Changes
                                </button>

                                <div id="rejectPrintModal{{ $order->id }}" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
                                    <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Request Print Changes</h3>
                                            <i class="fas fa-times" style="cursor: pointer; color: #94a3b8; font-size: 1.2rem;" onclick="document.getElementById('rejectPrintModal{{ $order->id }}').style.display='none'"></i>
                                        </div>
                                         <form action="{{ route('crm.production_jobs.sales_agent_review', $order->productionJob->id) }}" method="POST">
                                             {{ csrf_field() }}
                                             <input type="hidden" name="action" value="request_change">
                                             <div style="margin-bottom: 1rem;">
                                                 <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Notes / Rejection Reason</label>
                                                 <textarea name="notes" rows="3" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none;" placeholder="e.g. The color is too dark..."></textarea>
                                             </div>
                                             <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-purple); color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 1rem; cursor: pointer;">
                                                 Send Back to Press Operator
                                             </button>
                                         </form>
                                    </div>
                                </div>
                            @endif
                        @endif
                        <a href="{{ route('crm.emails.show', $order->crm_email_id) }}" style="text-decoration: none; background: #f1f5f9; color: #475569; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">View Lead</a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #64748b;">No Sales Orders found.</td>
            </tr>
        @endforelse

        @if($salesOrders->hasPages())
        <tr style="border: none;">
            <td colspan="7" style="padding: 15px 0;">
                <div style="display:flex; justify-content: center;" class="pagination-wrapper">
                    {{ $salesOrders->appends(request()->query())->links() }}
                </div>
            </td>
        </tr>
        @endif
    </tbody>
</table>
