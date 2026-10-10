{{-- Sortable column header. Vars: key, label, ascLabel, descLabel; uses $sort / $dir from the index. --}}
@php
    $__sorted = ($sort ?? '') === $key;
    $__nextDir = $__sorted && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc';
    $__params = array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => $key, 'dir' => $__nextDir]);
@endphp
<th class="dj-sortable {{ $__sorted ? 'is-sorted' : '' }}">
    <a href="{{ route('crm.design_jobs.index', $__params) }}" title="Sort by {{ strtolower($label) }} ({{ $__nextDir === 'asc' ? $ascLabel : $descLabel }})">
        {{ $label }}
        <span class="dj-sort-icons" aria-hidden="true">
            <i class="fas fa-caret-up {{ $__sorted && $dir === 'asc' ? 'on' : '' }}"></i>
            <i class="fas fa-caret-down {{ $__sorted && $dir === 'desc' ? 'on' : '' }}"></i>
        </span>
    </a>
</th>
