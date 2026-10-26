{{-- "Make Payment" dialog on the Payments page: a direct company payment booked against an expense category (stored as a CrmExpense). --}}
<div class="ex-modal gl-page" id="exModal" onclick="if (event.target === this) exClose()">
    <form class="ex-dialog" id="exForm" method="POST" action="{{ route('crm.expenses.store') }}" enctype="multipart/form-data">
        {{ csrf_field() }}
        <input type="hidden" name="_method" id="exMethod" value="POST">
        <input type="hidden" name="_expense_id" id="exId" value="">
        <div class="ex-dhead">
            <span class="gl-ico red"><i class="fas fa-hand-holding-usd"></i></span>
            <div><h3 id="exTitle">Make Payment</h3><p>Pick the expense category this payment is for. It is listed here under Payments.</p></div>
            <button type="button" class="ex-close" onclick="exClose()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            @if($errors->any())
                <div class="ex-errors"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="ex-f"><label>Date <span class="req">*</span></label><input type="date" name="expense_date" id="exDate" max="{{ now()->toDateString() }}" required></div>
            <div class="ex-f"><label>Expense Category <span class="req">*</span> <span class="hint">(what is this payment for)</span></label>
                <input name="category" id="exCategory" list="exCategoryList" placeholder="e.g. Rent, Fuel, Repairs" required maxlength="80">
                <datalist id="exCategoryList">@foreach($categoryOptions as $c)<option value="{{ $c }}">@endforeach</datalist>
            </div>
            <div class="ex-f full"><label>Description <span class="req">*</span></label><input name="name" id="exName" placeholder="e.g. Warehouse rent October, Delivery van diesel" required maxlength="255"></div>
            <div class="ex-f"><label>Amount <span class="req">*</span></label>
                <div class="ex-amount">
                    <input type="number" name="amount" id="exAmount" step="0.01" min="0.01" placeholder="0.00" required>
                    <select name="currency" id="exCurrency">@foreach(['AED', 'USD', 'GBP', 'EUR'] as $cur)<option>{{ $cur }}</option>@endforeach</select>
                </div>
            </div>
            <div class="ex-f"><label>Paid From <span class="req">*</span></label>
                <select name="paid_from" id="exPaidFrom" required>
                    <option value="">Select...</option>
                    @foreach($paidFromOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                </select>
            </div>
            @include('crm.bank_accounts._select', ['baId' => 'exBankAccount', 'baWrapClass' => 'ex-f full'])
            <div class="ex-f full"><label>Paid To <span class="req">*</span></label>
                <div class="ex-seg">
                    @foreach($payeeTypes as $value => $payeeLabel)
                        <label><input type="radio" name="payee_type" value="{{ $value }}" onchange="exPayeeType(this.value)" {{ $value === 'supplier' ? 'checked' : '' }}><span><i class="fas {{ ['supplier' => 'fa-truck', 'customer' => 'fa-user', 'other' => 'fa-ellipsis-h'][$value] }}"></i> {{ $payeeLabel }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="ex-f full"><label id="exPayeeLabel">Supplier Name</label>
                <input name="payee_name" id="exPayeeName" list="exSupplierList" maxlength="255" placeholder="Pick from the list or type a name">
                <datalist id="exSupplierList">@foreach($supplierNames as $n)<option value="{{ $n }}">@endforeach</datalist>
                <datalist id="exCustomerList">@foreach($customerNames as $n)<option value="{{ $n }}">@endforeach</datalist>
            </div>
            <div class="ex-f"><label>Reference <span class="hint">(bill / receipt #)</span></label><input name="reference" id="exReference" maxlength="120"></div>
            <div class="ex-f"><label>Proof <span class="hint">(image / PDF, optional)</span></label><input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf"><div class="ex-current-proof" id="exCurrentProof"></div></div>
            <div class="ex-f full"><label>Note</label><textarea name="note" id="exNote" rows="2" maxlength="500"></textarea></div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="ex-cancel" onclick="exClose()">Cancel</button>
            <button type="submit" class="ex-add" id="exSubmit"><i class="fas fa-check"></i> Save Payment</button>
        </div>
    </form>
</div>

<script>
    var exStoreUrl = @json(route('crm.expenses.store'));
    var exUpdateUrl = @json(route('crm.expenses.update', '__ID__'));
    var exModal = document.getElementById('exModal');
    var exForm = document.getElementById('exForm');

    function exPayeeType(type) {
        var input = document.getElementById('exPayeeName');
        var label = document.getElementById('exPayeeLabel');
        input.setAttribute('list', type === 'supplier' ? 'exSupplierList' : (type === 'customer' ? 'exCustomerList' : ''));
        label.textContent = type === 'supplier' ? 'Supplier Name' : (type === 'customer' ? 'Customer Name' : 'Payee Name');
        input.placeholder = type === 'other' ? 'e.g. DEWA, Employee name, Government office' : 'Pick from the list or type a name';
    }

    function exFill(data) {
        document.getElementById('exDate').value = data.expense_date || @json(now()->toDateString());
        document.getElementById('exCategory').value = data.category || '';
        document.getElementById('exName').value = data.name || '';
        document.getElementById('exAmount').value = data.amount || '';
        document.getElementById('exCurrency').value = data.currency || 'AED';
        document.getElementById('exPaidFrom').value = data.paid_from || '';
        if (document.getElementById('exBankAccount')) document.getElementById('exBankAccount').value = data.bank_account_id || '';
        document.getElementById('exReference').value = data.reference || '';
        document.getElementById('exNote').value = data.note || '';
        document.getElementById('exPayeeName').value = data.payee_name || '';
        var type = data.payee_type || 'supplier';
        exForm.querySelectorAll('input[name=payee_type]').forEach(function (r) { r.checked = r.value === type; });
        exPayeeType(type);
        document.getElementById('exCurrentProof').textContent = data.proof_name ? 'Current: ' + data.proof_name + ' (choose a file to replace it)' : '';
    }

    function exOpenCreate() {
        exForm.action = exStoreUrl;
        document.getElementById('exMethod').value = 'POST';
        document.getElementById('exId').value = '';
        document.getElementById('exTitle').textContent = 'Make Payment';
        exFill({});
        exModal.classList.add('open');
        document.getElementById('exCategory').focus();
    }

    function exOpenEdit(data) {
        exForm.action = exUpdateUrl.replace('__ID__', data.id);
        document.getElementById('exMethod').value = 'PUT';
        document.getElementById('exId').value = data.id;
        document.getElementById('exTitle').textContent = 'Edit Payment ' + data.number;
        exFill(data);
        exModal.classList.add('open');
    }

    function exClose() { exModal.classList.remove('open'); }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') exClose(); });

    // After a failed save, reopen the form with what the user typed.
    @if($errors->any())
        (function () {
            var old = @json(old());
            if (old._expense_id) {
                exOpenEdit(Object.assign({}, old, { id: old._expense_id, number: '' }));
            } else {
                exOpenCreate();
                exFill(old);
            }
        })();
    @endif
</script>
