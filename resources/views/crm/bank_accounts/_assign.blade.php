{{-- Small "booked to" selector for one movement. Needs $m (link_type, link_id, account_id, auto) and $accounts. --}}
@if($m->link_type)
<form method="POST" action="{{ route('crm.bank_accounts.assign') }}" class="ba-assign">
    {{ csrf_field() }}
    <input type="hidden" name="link_type" value="{{ $m->link_type }}">
    <input type="hidden" name="link_id" value="{{ $m->link_id }}">
    <select name="bank_account_id" onchange="this.form.submit()" title="Book this entry to another account">
        <option value="" {{ $m->auto ? 'selected' : '' }}>{{ $m->auto ? 'Auto (by method)' : 'Back to auto' }}</option>
        @foreach($accounts as $a)
            <option value="{{ $a->id }}" {{ !$m->auto && (int) $m->account_id === (int) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
        @endforeach
    </select>
</form>
@else
<span class="gl-mutedtext" title="This amount has no separate payment record (e.g. a legacy invoice balance) and follows its method">auto</span>
@endif
