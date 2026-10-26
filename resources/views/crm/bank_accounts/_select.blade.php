{{-- "Bank / Cash Account" dropdown for payment forms. Renders nothing until an account exists.
     Params are prefixed "ba" so the including page's own $label / $id / $class variables never leak in:
     $baSelected (id|null), $baId (element id), $baClass, $baLabel, $baDirection (in|out), $baWrapClass, $baWrapId, $baPlain (select only). --}}
@php $__accounts = \App\CrmBankAccount::options(); @endphp
@if($__accounts->isNotEmpty())
    @php
        $__select = '<select name="bank_account_id"' . (!empty($baId) ? ' id="' . e($baId) . '"' : '') . (!empty($baClass) ? ' class="' . e($baClass) . '"' : '') . '>'
            . '<option value="">Auto (by payment method)</option>';
        foreach ($__accounts as $__a) {
            $__select .= '<option value="' . $__a->id . '"' . ((int) ($baSelected ?? 0) === $__a->id ? ' selected' : '') . '>'
                . e($__a->name) . ($__a->is_active ? '' : ' (closed)') . '</option>';
        }
        $__select .= '</select>';
    @endphp
    @if(!empty($baPlain))
        {!! $__select !!}
    @else
        <div class="{{ $baWrapClass ?? 'ex-f' }}" @if(!empty($baWrapId)) id="{{ $baWrapId }}" @endif><label>@if(!empty($baLabel)){{ $baLabel }}@else Bank / Cash Account <span class="hint">(money {{ ($baDirection ?? 'out') === 'in' ? 'received in' : 'paid from' }})</span>@endif</label>{!! $__select !!}</div>
    @endif
@endif
