{{-- One "Export" button that opens Excel / PDF. Pass excelUrl and pdfUrl, optionally hint. Styles and closing behaviour: crm.layout. --}}
<details class="crm-export">
    <summary title="{{ $hint ?? 'Export' }}"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down crm-export-caret"></i></summary>
    <div class="crm-export-options">
        <a href="{{ $excelUrl }}" data-no-ajax-nav><i class="fas fa-file-excel" style="color:#15803d"></i> Excel</a>
        <a href="{{ $pdfUrl }}" data-no-ajax-nav><i class="fas fa-file-pdf" style="color:#dc2626"></i> PDF</a>
    </div>
</details>
