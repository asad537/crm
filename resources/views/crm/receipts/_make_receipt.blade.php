{{-- "Make Receipt" dialog: a payment on an open invoice, or other income received without an invoice (CrmDirectReceipt). --}}
<style>
    .mr-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; padding: 1rem 1.3rem 0; }
    .mr-tab { border: 1px solid var(--line); background: #fff; border-radius: 10px; padding: .65rem .5rem; font-weight: 800; font-size: .82rem; color: var(--muted); cursor: pointer; font-family: inherit; }
    .mr-tab i { margin-right: .35rem; }
    .mr-tab.active { border-color: var(--brand); background: var(--brand-soft); color: var(--brand); }
    .mr-due { font-size: .74rem; color: var(--muted); margin-top: .3rem; }
    .mr-due b { color: var(--red); }
</style>
<div class="ex-modal gl-page" id="mrModal" onclick="if (event.target === this) mrClose()">
    <div class="ex-dialog">
        <div class="ex-dhead">
            <span class="gl-ico green"><i class="fas fa-hand-holding-usd"></i></span>
            <div><h3 id="mrTitle">Make Receipt</h3><p>Money received: against a customer's invoice, or other income without an invoice.</p></div>
            <button type="button" class="ex-close" onclick="mrClose()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="mr-tabs" id="mrTabs">
            <button type="button" class="mr-tab active" data-mode="invoice" onclick="mrMode('invoice')"><i class="fas fa-file-invoice"></i> Invoice Payment</button>
            <button type="button" class="mr-tab" data-mode="other" onclick="mrMode('other')"><i class="fas fa-coins"></i> Other Income</button>
        </div>

        {{-- From a customer: on one of their open invoices / sales / dues, or on account (no invoice picked). --}}
        <form id="mrInvoiceForm" method="POST" action="">
            {{ csrf_field() }}
            <input type="hidden" name="return_to" value="receipts">
            <input type="hidden" name="doc_type" id="mrDocType" value="">
            <input type="hidden" name="doc_id" id="mrDocId" value="">
            <input type="hidden" name="customer" id="mrCustomerName" value="">
            <div class="ex-dbody">
                @if($errors->any() && old('receipt_date') === null)
                    <div class="ex-errors"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <div class="ex-f full"><label>Customer <span class="req">*</span></label>
                    <select id="mrCustomer" onchange="mrFillInvoices()">
                        @foreach($receiptCustomers as $c)<option value="{{ $c['name'] }}">{{ $c['name'] }}{{ $c['due'] > 0 ? ' · due ' . number_format($c['due'], 2) : '' }}</option>@endforeach
                    </select>
                </div>
                <div class="ex-f full"><label>Invoice <span class="req">*</span></label>
                    <select id="mrInvoice" required onchange="mrPickInvoice()"></select>
                    <div class="mr-due" id="mrDue"></div>
                </div>
                <div class="ex-f"><label>Amount Received <span class="req">*</span></label><input type="number" name="amount" id="mrInvAmount" step="0.01" min="0.01" required></div>
                <div class="ex-f"><label>Date <span class="req">*</span></label><input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
                <div class="ex-f"><label>Method</label><select name="method">@foreach($receiptMethods as $m)<option value="{{ $m }}" {{ $m === 'Bank Transfer' ? 'selected' : '' }}>{{ $m }}</option>@endforeach</select></div>
                @include('crm.bank_accounts._select', ['baId' => 'mrInvAccount', 'baDirection' => 'in'])
                <div class="ex-f"><label>Reference <span class="hint">(cheque / transfer #)</span></label><input name="reference" maxlength="120"></div>
                <div class="ex-f"><label>Note</label><input name="note" maxlength="500"></div>
            </div>
            <div class="ex-dfoot">
                <button type="button" class="ex-cancel" onclick="mrClose()">Cancel</button>
                <button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Receipt</button>
            </div>
        </form>

        {{-- Other income: no invoice, booked to an income category. --}}
        <form id="mrOtherForm" method="POST" action="{{ route('crm.direct_receipts.store') }}" enctype="multipart/form-data" style="display:none">
            {{ csrf_field() }}
            <input type="hidden" name="_method" id="mrOtherMethod" value="POST">
            <input type="hidden" name="_receipt_id" id="mrOtherId" value="">
            <div class="ex-dbody">
                @if($errors->any() && old('receipt_date') !== null)
                    <div class="ex-errors"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <div class="ex-f"><label>Date <span class="req">*</span></label><input type="date" name="receipt_date" id="mrDate" max="{{ now()->toDateString() }}" required></div>
                <div class="ex-f"><label>Income Category <span class="req">*</span></label>
                    <input name="category" id="mrCategory" list="mrCategoryList" placeholder="e.g. Scrap Sales" required maxlength="80">
                    <datalist id="mrCategoryList">@foreach($receiptCategories as $c)<option value="{{ $c }}">@endforeach</datalist>
                </div>
                <div class="ex-f full"><label>Description <span class="req">*</span></label><input name="description" id="mrDescription" required maxlength="255" placeholder="e.g. Sold waste paper and board cuttings"></div>
                <div class="ex-f"><label>Amount <span class="req">*</span></label>
                    <div class="ex-amount">
                        <input type="number" name="amount" id="mrAmount" step="0.01" min="0.01" required placeholder="0.00">
                        <select name="currency" id="mrCurrency">@foreach(['AED', 'USD', 'GBP', 'EUR'] as $cur)<option>{{ $cur }}</option>@endforeach</select>
                    </div>
                </div>
                <div class="ex-f"><label>Method <span class="req">*</span></label><select name="method" id="mrMethod" required>@foreach($receiptMethods as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
                @include('crm.bank_accounts._select', ['baId' => 'mrOtherAccount', 'baDirection' => 'in', 'baWrapClass' => 'ex-f full'])
                <div class="ex-f full"><label>Received From</label>
                    <div class="ex-seg" style="grid-template-columns:1fr 1fr">
                        @foreach($payerTypes as $value => $payerLabel)
                            <label><input type="radio" name="payer_type" value="{{ $value }}" onchange="mrPayer(this.value)" {{ $value === 'other' ? 'checked' : '' }}><span><i class="fas {{ $value === 'customer' ? 'fa-user' : 'fa-ellipsis-h' }}"></i> {{ $payerLabel }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="ex-f full"><label id="mrPayerLabel">Name</label>
                    <input name="payer_name" id="mrPayerName" maxlength="255" placeholder="Who paid">
                    <datalist id="mrCustomerList">@foreach($payerCustomers as $n)<option value="{{ $n }}">@endforeach</datalist>
                </div>
                <div class="ex-f"><label>Reference</label><input name="reference" id="mrReference" maxlength="120"></div>
                <div class="ex-f"><label>Proof <span class="hint">(image / PDF)</span></label><input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf"><div class="ex-current-proof" id="mrCurrentProof"></div></div>
                <div class="ex-f full"><label>Note</label><input name="note" id="mrNote" maxlength="500"></div>
            </div>
            <div class="ex-dfoot">
                <button type="button" class="ex-cancel" onclick="mrClose()">Cancel</button>
                <button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Receipt</button>
            </div>
        </form>
    </div>
</div>

<script>
    var mrInvoices = @json($openInvoices);
    var mrInvoiceUrl = @json(route('crm.orders.payments.store', '__ID__'));
    var mrReceiveUrl = @json(route('crm.receipts.receive'));
    var mrOtherUrls = { store: @json(route('crm.direct_receipts.store')), update: @json(route('crm.direct_receipts.update', '__ID__')) };
    var mrModal = document.getElementById('mrModal');

    function mrMoney(n) { return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function mrMode(mode) {
        document.querySelectorAll('#mrTabs .mr-tab').forEach(function (t) { t.classList.toggle('active', t.dataset.mode === mode); });
        document.getElementById('mrInvoiceForm').style.display = mode === 'invoice' ? '' : 'none';
        document.getElementById('mrOtherForm').style.display = mode === 'other' ? '' : 'none';
    }

    // Each option's value is "type:id" (order / sale / income), or "account" for a payment not tied to one invoice.
    function mrFillInvoices() {
        var select = document.getElementById('mrInvoice');
        var customer = document.getElementById('mrCustomer').value;
        select.innerHTML = '';
        mrInvoices.filter(function (i) { return i.customer === customer; }).forEach(function (i) {
            var o = document.createElement('option');
            o.value = i.type + ':' + i.id;
            o.textContent = i.ref + (i.kind && i.kind !== 'Invoice' ? ' (' + i.kind + ')' : '') + ' · ' + (i.date || '') + ' · due ' + mrMoney(i.due) + ' ' + i.currency;
            select.appendChild(o);
        });
        var account = document.createElement('option');
        account.value = 'account';
        account.textContent = select.options.length ? 'No specific invoice — payment on account' : 'No open invoice — payment on account / advance';
        select.appendChild(account);
        mrPickInvoice();
    }

    function mrPickInvoice() {
        var select = document.getElementById('mrInvoice');
        var form = document.getElementById('mrInvoiceForm');
        var amount = document.getElementById('mrInvAmount');
        var customer = document.getElementById('mrCustomer').value;
        document.getElementById('mrCustomerName').value = customer;
        if (select.value === 'account') {
            form.action = mrReceiveUrl;
            document.getElementById('mrDocType').value = 'account';
            document.getElementById('mrDocId').value = '';
            amount.value = '';
            amount.removeAttribute('max');
            document.getElementById('mrDue').innerHTML = 'Not tied to one invoice: it is taken off what ' + customer + ' owes in total (or kept as their advance). It is not income.';
            return;
        }
        var parts = select.value.split(':');
        var inv = mrInvoices.find(function (i) { return i.type === parts[0] && String(i.id) === parts[1]; });
        if (!inv) return;
        // Al Massa invoices use the invoice's own payment action; sales and rent dues use the receipts one.
        form.action = inv.type === 'order' ? mrInvoiceUrl.replace('__ID__', inv.id) : mrReceiveUrl;
        document.getElementById('mrDocType').value = inv.type;
        document.getElementById('mrDocId').value = inv.id;
        amount.value = inv.due;
        amount.max = inv.due;
        document.getElementById('mrDue').innerHTML = (inv.kind || 'Invoice') + ' total ' + mrMoney(inv.total) + ' · still due <b>' + mrMoney(inv.due) + ' ' + inv.currency + '</b>';
    }

    function mrPayer(type) {
        var input = document.getElementById('mrPayerName');
        input.setAttribute('list', type === 'customer' ? 'mrCustomerList' : '');
        document.getElementById('mrPayerLabel').textContent = type === 'customer' ? 'Customer Name' : 'Name';
        input.placeholder = type === 'customer' ? 'Pick from the list or type a name' : 'Who paid (e.g. scrap dealer, bank)';
    }

    function mrFillOther(data) {
        document.getElementById('mrDate').value = data.receipt_date || @json(now()->toDateString());
        document.getElementById('mrCategory').value = data.category || '';
        document.getElementById('mrDescription').value = data.description || '';
        document.getElementById('mrAmount').value = data.amount || '';
        document.getElementById('mrCurrency').value = data.currency || 'AED';
        document.getElementById('mrMethod').value = data.method || 'Cash';
        if (document.getElementById('mrOtherAccount')) document.getElementById('mrOtherAccount').value = data.bank_account_id || '';
        document.getElementById('mrPayerName').value = data.payer_name || '';
        document.getElementById('mrReference').value = data.reference || '';
        document.getElementById('mrNote').value = data.note || '';
        var type = data.payer_type || 'other';
        document.querySelectorAll('#mrOtherForm input[name=payer_type]').forEach(function (r) { r.checked = r.value === type; });
        mrPayer(type);
        document.getElementById('mrCurrentProof').textContent = data.proof_name ? 'Current: ' + data.proof_name + ' (choose a file to replace it)' : '';
    }

    function mrOpenCreate(mode) {
        var form = document.getElementById('mrOtherForm');
        form.action = mrOtherUrls.store;
        document.getElementById('mrOtherMethod').value = 'POST';
        document.getElementById('mrOtherId').value = '';
        document.getElementById('mrTitle').textContent = 'Make Receipt';
        document.getElementById('mrTabs').style.display = '';
        mrFillOther({});
        mrFillInvoices();
        mrMode(mode || 'invoice');
        mrModal.classList.add('open');
    }

    function mrOpenEdit(data) {
        var form = document.getElementById('mrOtherForm');
        form.action = mrOtherUrls.update.replace('__ID__', data.id);
        document.getElementById('mrOtherMethod').value = 'PUT';
        document.getElementById('mrOtherId').value = data.id;
        document.getElementById('mrTitle').textContent = 'Edit Receipt ' + (data.number || '');
        document.getElementById('mrTabs').style.display = 'none';
        mrFillOther(data);
        mrMode('other');
        mrModal.classList.add('open');
    }

    function mrClose() { mrModal.classList.remove('open'); }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') mrClose(); });

    // After a failed save, reopen the right form with what was typed.
    @if($errors->any())
        (function () {
            var old = @json(old());
            if (old.receipt_date !== undefined) {
                if (old._receipt_id) mrOpenEdit(Object.assign({}, old, { id: old._receipt_id }));
                else { mrOpenCreate('other'); mrFillOther(old); }
            } else {
                mrOpenCreate('invoice');
            }
        })();
    @endif
</script>
