{{-- Company letterhead with logo (matches the vendor ledger PDF). Needs $meta and $companyName; optional $documentTitle. --}}
@php
    $logoPath = public_path($meta['isAlMassa'] ? 'al-massa-packaging-logo-pdf.jpg' : 'my-box-printing-logo-pdf.jpg');
    $logoData = is_file($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;
@endphp
<table class="letterhead"><tr>
    <td class="logo-cell">@if($logoData)<img class="logo" src="{{ $logoData }}">@endif</td>
    <td class="company-cell">
        <div class="company-name">{{ $companyName }}</div>
        @if($meta['isAlMassa'])
            <div class="company-line">All Cosmetics &amp; Perfumes Hard, Soft Boxes and Paper Bags</div>
            <div class="company-line">Al Diyar Building 33, 4th Industrial Street, Industrial Area 12,<br>Sharjah, United Arab Emirates</div>
            <div class="company-line">+971 6 579 6994 &nbsp;|&nbsp; +971 56 997 0652</div>
            <div class="company-line">info@almassapackaging.com</div>
        @else
            <div class="company-line">Premium Custom Packaging Solutions</div>
            <div class="company-line">www.myboxprinting.com &nbsp;|&nbsp; support@myboxprinting.com</div>
        @endif
    </td>
    <td class="document-cell">{{ $documentTitle ?? 'GENERAL LEDGER' }}</td>
</tr></table>
<div class="letter-rule"></div>
