<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmail;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = $this->baseReportQuery($request);

        $ordersQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('status', 'Order Done')->orWhereHas('salesOrder');
        });

        $totalOrders = (clone $ordersQuery)->count();
        $totalSales = (clone $ordersQuery)->sum(DB::raw('COALESCE(order_price, 0) * COALESCE(order_quantity, 0)'));
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        $pendingOrders = (clone $ordersQuery)->where(function ($q) {
            $q->whereDoesntHave('salesOrder')
                ->orWhereHas('salesOrder', function ($sq) {
                    $sq->whereNotIn('shipping_stage', ['delivered', 'payment_posted', 'order_completed'])
                       ->whereNotIn('status', ['cancelled', 'rejected']);
                });
        })->count();

        $deliveredOrders = (clone $ordersQuery)->whereHas('salesOrder', function ($q) {
            $q->whereIn('shipping_stage', ['delivered', 'payment_posted', 'order_completed'])
              ->orWhereIn('status', ['delivered', 'payment_posted', 'order_completed']);
        })->count();

        $rejectedOrders = (clone $baseQuery)->where(function ($q) {
            $q->where('status', 'Rejected')
              ->orWhereHas('salesOrder', function ($sq) {
                  $sq->whereIn('status', ['rejected', 'cancelled']);
              });
        })->count();

        $records = (clone $baseQuery)
            ->with('salesOrder')
            ->orderByRaw('COALESCE(order_marked_at, created_at) DESC')
            ->paginate(25)
            ->appends($request->all());

        $dayWise = $this->salesSummary($ordersQuery, 'day');
        $monthWise = $this->salesSummary($ordersQuery, 'month');

        $agents = CrmEmail::whereNotNull('order_marked_by')->distinct()->orderBy('order_marked_by')->pluck('order_marked_by');

        return view('crm.reports.index', compact(
            'records',
            'agents',
            'totalOrders',
            'pendingOrders',
            'deliveredOrders',
            'rejectedOrders',
            'totalSales',
            'avgOrderValue',
            'dayWise',
            'monthWise'
        ));
    }

    public function export(Request $request)
    {
        $records = $this->baseReportQuery($request)
            ->with('salesOrder')
            ->orderByRaw('COALESCE(order_marked_at, created_at) DESC')
            ->get();

        $fileName = 'crm-report-' . now()->format('Y-m-d-His') . '.xlsx';
        $dateRangeLabel = $this->exportDateRangeLabel($request);
        $generatedAt = now()->format('Y-m-d h:i A');
        $brand = $this->reportBranding();
        $filePath = $this->buildReportWorkbook($records, $dateRangeLabel, $generatedAt, $brand);

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        $records = $this->baseReportQuery($request)
            ->with('salesOrder')
            ->orderByRaw('COALESCE(order_marked_at, created_at) DESC')
            ->get();

        $fileName = 'crm-report-' . now()->format('Y-m-d-His') . '.pdf';
        $dateRangeLabel = $this->exportDateRangeLabel($request);
        $generatedAt = now()->format('Y-m-d h:i A');
        $brand = $this->reportBranding();
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.reports.export_pdf', compact('records', 'dateRangeLabel', 'generatedAt', 'brand'))
            ->setPaper('a4', 'landscape')->download($fileName);
    }

    private function reportBranding()
    {
        $workspace = view()->shared('activeCrmWorkspace');
        $isAlMassa = $workspace && $workspace->slug === 'mybox-packaging-app';
        $logoFile = $isAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo-pdf.jpg';
        $logoPath = collect([base_path($logoFile), rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . $logoFile, public_path($logoFile), base_path('public/'.$logoFile)])->first(function ($path) { return $path && is_file($path); });
        $logo = $logoPath ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath)) : '';
        return [
            'name' => $isAlMassa ? 'Al Massa Packaging' : 'My Box Printing',
            'company' => $isAlMassa ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING',
            'color' => $isAlMassa ? '#0d2f68' : '#80c500',
            'accent' => $isAlMassa ? '#e4a000' : '#80c500',
            'is_al_massa' => $isAlMassa,
            'xlsx_color' => $isAlMassa ? 'FF0D2F68' : 'FF80C500',
            'logo' => $logo,
        ];
    }

    private function buildReportPdf($records, $dateRangeLabel, $generatedAt)
    {
        $filePath = tempnam(sys_get_temp_dir(), 'crm-report-pdf-');
        $rows = $this->reportExportRows($records);
        $chunks = array_chunk($rows, 24);
        if (empty($chunks)) {
            $chunks = [[]];
        }

        $objects = [];
        $pages = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $nextObject = 5;
        foreach ($chunks as $pageIndex => $chunk) {
            $contentObject = $nextObject++;
            $pageObject = $nextObject++;
            $objects[$contentObject] = $this->pdfStream($this->pdfPageContent(
                $chunk,
                $dateRangeLabel,
                $generatedAt,
                $pageIndex + 1,
                count($chunks),
                $records->count()
            ));
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
            $pages[] = $pageObject . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pages) . '] /Count ' . count($pages) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (max(array_keys($objects)) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= max(array_keys($objects)); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . (max(array_keys($objects)) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        file_put_contents($filePath, $pdf);

        return $filePath;
    }

    private function reportExportRows($records)
    {
        return $records->map(function ($record) {
            $salesOrder = $record->salesOrder;
            $status = $record->status === 'Rejected'
                ? 'Rejected'
                : ($salesOrder->shipping_stage ?? $salesOrder->status ?? $record->status);
            $lineTotal = (float) ($record->order_price ?? 0) * (float) ($record->order_quantity ?? 0);
            $date = $record->order_marked_at
                ? Carbon::parse($record->order_marked_at)
                : $record->created_at;

            return [
                $record->id,
                $record->client_name ?: 'Unknown',
                $record->client_email,
                $record->product_name ?: 'General',
                ucwords(str_replace('_', ' ', $status ?: 'Pending')),
                $record->order_quantity ?? 0,
                '$' . number_format((float) ($record->order_price ?? 0), 2),
                '$' . number_format($lineTotal, 2),
                $record->order_marked_by ?: '',
                $record->payment_status ?: '',
                $record->shipping_address ?: $record->billing_address ?: '',
                $date ? $date->format('d/m/Y') : '',
            ];
        })->all();
    }

    private function pdfPageContent(array $rows, $dateRangeLabel, $generatedAt, $page, $totalPages, $totalRecords)
    {
        $content = '';
        $content .= "0.985 0.995 0.975 rg 28 510 786 55 re f\n";
        $content .= "0.423 0.733 0.145 rg 28 510 7 55 re f\n";
        $content .= "0.86 0.93 0.80 RG 0.7 w 28 510 786 55 re S\n";
        $content .= $this->pdfText('Master Report', 48, 543, 18, '0.06 0.12 0.05', 'F2');
        $content .= $this->pdfText($dateRangeLabel, 48, 524, 10, '0.20 0.34 0.14', 'F2');
        $content .= $this->pdfText('Generated: ' . $generatedAt, 615, 544, 8.5, '0.36 0.42 0.48');
        $content .= $this->pdfText('Records: ' . number_format($totalRecords), 615, 529, 8.5, '0.36 0.42 0.48');
        $content .= $this->pdfText('Page ' . $page . ' of ' . $totalPages, 615, 514, 8.5, '0.36 0.42 0.48');

        $headers = ['ID', 'Client', 'Email', 'Product', 'Status', 'Qty', 'Unit', 'Total', 'Agent', 'Payment', 'Address', 'Date'];
        $widths = [34, 94, 128, 70, 74, 32, 44, 50, 44, 50, 95, 55];
        $x = 36;
        $y = 480;
        $rowHeight = 18;

        $content .= "0.18 0.33 0.13 rg " . $x . " " . $y . " 770 " . $rowHeight . " re f\n";
        foreach ($headers as $index => $header) {
            $content .= $this->pdfText($header, $x + 4, $y + 6, 7.2, '1 1 1', 'F2');
            $x += $widths[$index];
        }

        $y -= $rowHeight;
        if (empty($rows)) {
            $content .= "0.965 0.985 0.94 rg 36 " . $y . " 770 " . $rowHeight . " re f\n";
            $content .= $this->pdfText('No records found for the selected filters.', 42, $y + 6, 8.5, '0.35 0.4 0.45');
        }

        foreach ($rows as $rowIndex => $row) {
            $x = 36;
            if ($rowIndex % 2 === 0) {
                $content .= "0.965 0.985 0.94 rg " . $x . " " . $y . " 770 " . $rowHeight . " re f\n";
            }
            $content .= "0.86 0.93 0.81 RG 0.35 w " . $x . " " . $y . " 770 " . $rowHeight . " re S\n";

            foreach ($row as $index => $value) {
                $alignRight = in_array($index, [0, 5, 6, 7], true);
                $fontSize = $alignRight ? 6.8 : 6.6;
                $text = $this->pdfFitText($value, $widths[$index], $fontSize);
                $textX = $alignRight ? $x + $widths[$index] - 4 - (strlen($text) * ($fontSize * 0.47)) : $x + 4;
                $content .= $this->pdfText($text, max($x + 4, $textX), $y + 6, $fontSize, '0.08 0.12 0.08');
                $x += $widths[$index];
            }
            $y -= $rowHeight;
        }

        $content .= "0.82 0.88 0.78 RG 0.4 w 36 34 m 806 34 l S\n";
        $content .= $this->pdfText('MyBox CRM', 36, 20, 7.5, '0.45 0.5 0.55');
        $content .= $this->pdfText('Confidential report export', 687, 20, 7.5, '0.45 0.5 0.55');

        return $content;
    }

    private function pdfStream($content)
    {
        return '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
    }

    private function pdfText($text, $x, $y, $size, $rgb, $font = 'F1')
    {
        return $rgb . " rg BT /" . $font . " " . $size . " Tf " . $x . " " . $y . " Td (" . $this->pdfEscape($text) . ") Tj ET\n";
    }

    private function pdfEscape($text)
    {
        $text = preg_replace('/[^\x20-\x7E]/', ' ', (string) $text);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function pdfFitText($text, $width, $fontSize)
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $text));
        $maxChars = max(4, (int) floor(($width - 6) / ($fontSize * 0.48)));

        if (strlen($text) <= $maxChars) {
            return $text;
        }

        return substr($text, 0, max(1, $maxChars - 3)) . '...';
    }

    private function buildReportWorkbook($records, $dateRangeLabel, $generatedAt, array $brand)
    {
        $filePath = tempnam(sys_get_temp_dir(), 'crm-report-');
        $zip = new ZipArchive();
        $zip->open($filePath, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCoreXml($generatedAt));
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml($brand['xlsx_color']));
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheetXml($records, $dateRangeLabel, $generatedAt, $brand['name']));
        $zip->close();

        return $filePath;
    }

    private function xlsxSheetXml($records, $dateRangeLabel, $generatedAt, $brandName)
    {
        $headers = [
            'ID',
            'Client',
            'Email',
            'Phone',
            'Product',
            'Status',
            'Quantity',
            'Unit Price',
            'Total',
            'Agent',
            'Payment Status',
            'Address',
            'Date',
        ];

        $rows = [
            $this->xlsxRow(1, [[$brandName . ' - Master Report', 1]]),
            $this->xlsxRow(2, [[$dateRangeLabel, 2]]),
            $this->xlsxRow(3, [['Generated At: ' . $generatedAt, 3]]),
            $this->xlsxRow(5, array_map(function ($header) {
                return [$header, 4];
            }, $headers)),
        ];

        $rowNumber = 6;
        foreach ($records as $record) {
            $salesOrder = $record->salesOrder;
            $status = $record->status === 'Rejected'
                ? 'Rejected'
                : ($salesOrder->shipping_stage ?? $salesOrder->status ?? $record->status);
            $lineTotal = (float) ($record->order_price ?? 0) * (float) ($record->order_quantity ?? 0);
            $date = $record->order_marked_at
                ? Carbon::parse($record->order_marked_at)
                : $record->created_at;

            $rows[] = $this->xlsxRow($rowNumber, [
                [$record->id, 5, 'n'],
                [$record->client_name ?: 'Unknown', 6],
                [$record->client_email, 6],
                [$record->client_phone, 6],
                [$record->product_name ?: 'General', 6],
                [ucwords(str_replace('_', ' ', $status ?: 'Pending')), 6],
                [$record->order_quantity ?? 0, 7, 'n'],
                [number_format((float) ($record->order_price ?? 0), 2, '.', ''), 8, 'n'],
                [number_format($lineTotal, 2, '.', ''), 8, 'n'],
                [$record->order_marked_by ?: '', 6],
                [$record->payment_status ?: '', 6],
                [$record->shipping_address ?: $record->billing_address ?: '', 6],
                [$date ? $date->format('d/m/Y') : '', 6],
            ]);
            $rowNumber++;
        }

        $mergeCells = '<mergeCells count="3"><mergeCell ref="A1:M1"/><mergeCell ref="A2:M2"/><mergeCell ref="A3:M3"/></mergeCells>';
        if ($records->isEmpty()) {
            $rows[] = $this->xlsxRow(6, [['No records found for the selected filters.', 6]]);
            $mergeCells = '<mergeCells count="4"><mergeCell ref="A1:M1"/><mergeCell ref="A2:M2"/><mergeCell ref="A3:M3"/><mergeCell ref="A6:M6"/></mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>'
            . '<col min="1" max="1" width="10" customWidth="1"/>'
            . '<col min="2" max="3" width="24" customWidth="1"/>'
            . '<col min="4" max="4" width="16" customWidth="1"/>'
            . '<col min="5" max="6" width="20" customWidth="1"/>'
            . '<col min="7" max="7" width="12" customWidth="1"/>'
            . '<col min="8" max="9" width="14" customWidth="1"/>'
            . '<col min="10" max="11" width="18" customWidth="1"/>'
            . '<col min="12" max="12" width="34" customWidth="1"/>'
            . '<col min="13" max="13" width="14" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData>'
            . '<autoFilter ref="A5:M' . max(5, $rowNumber - 1) . '"/>'
            . $mergeCells
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    private function xlsxRow($rowNumber, array $cells)
    {
        $xml = '<row r="' . $rowNumber . '">';
        foreach ($cells as $index => $cell) {
            $column = $this->xlsxColumnName($index + 1);
            $value = $cell[0];
            $style = $cell[1] ?? 0;
            $type = $cell[2] ?? 's';

            if ($type === 'n' && is_numeric($value)) {
                $xml .= '<c r="' . $column . $rowNumber . '" s="' . $style . '"><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $column . $rowNumber . '" s="' . $style . '" t="inlineStr"><is><t>'
                    . $this->xlsxEscape($value)
                    . '</t></is></c>';
            }
        }
        $xml .= '</row>';

        return $xml;
    }

    private function xlsxColumnName($number)
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = (int) floor($number / 26);
        }

        return $name;
    }

    private function xlsxEscape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xlsxContentTypesXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function xlsxRootRelsXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function xlsxWorkbookXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Master Report" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function xlsxWorkbookRelsXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function xlsxAppXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>MyBox CRM</Application></Properties>';
    }

    private function xlsxCoreXml($generatedAt)
    {
        $created = Carbon::parse($generatedAt)->toAtomString();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            . 'xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Master Report</dc:title><dc:creator>MyBox CRM</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>'
            . '</cp:coreProperties>';
    }

    private function xlsxStylesXml($brandColor)
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;$&quot;#,##0.00"/></numFmts>'
            . '<fonts count="5">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="20"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="13"/><color rgb="FF365724"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><color rgb="FF64748B"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="' . $brandColor . '"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF6DF"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="' . $brandColor . '"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD8EACC"/></left><right style="thin"><color rgb="FFD8EACC"/></right><top style="thin"><color rgb="FFD8EACC"/></top><bottom style="thin"><color rgb="FFD8EACC"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="9">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function exportDateRangeLabel(Request $request)
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            return 'Report From ' . Carbon::parse($request->start_date)->format('d/m/Y')
                . ' To ' . Carbon::parse($request->end_date)->format('d/m/Y');
        }

        if ($request->filled('start_date')) {
            return 'Report From ' . Carbon::parse($request->start_date)->format('d/m/Y') . ' Onwards';
        }

        if ($request->filled('end_date')) {
            return 'Report Up To ' . Carbon::parse($request->end_date)->format('d/m/Y');
        }

        return 'Report For All Dates';
    }

    private function baseReportQuery(Request $request)
    {
        $query = CrmEmail::where('is_spam', false)
            ->where(function ($q) {
                $q->where('status', 'Order Done')
                  ->orWhere('status', 'Rejected')
                  ->orWhereHas('salesOrder');
            });

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $dateColumn = DB::raw('COALESCE(order_marked_at, created_at)');

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween($dateColumn, [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ]);
            } elseif ($request->filled('start_date')) {
                $query->where($dateColumn, '>=', Carbon::parse($request->start_date)->startOfDay());
            } else {
                $query->where($dateColumn, '<=', Carbon::parse($request->end_date)->endOfDay());
            }
        }

        if ($request->filled('agent')) {
            $query->where('order_marked_by', $request->agent);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'pending') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('salesOrder')
                      ->orWhereHas('salesOrder', function ($sq) {
                          $sq->whereNotIn('shipping_stage', ['delivered', 'payment_posted', 'order_completed'])
                             ->whereNotIn('status', ['cancelled', 'rejected']);
                      });
                });
            } elseif ($status === 'delivered') {
                $query->whereHas('salesOrder', function ($q) {
                    $q->whereIn('shipping_stage', ['delivered', 'payment_posted', 'order_completed'])
                      ->orWhereIn('status', ['delivered', 'payment_posted', 'order_completed']);
                });
            } elseif ($status === 'rejected') {
                $query->where(function ($q) {
                    $q->where('status', 'Rejected')
                      ->orWhereHas('salesOrder', function ($sq) {
                          $sq->whereIn('status', ['rejected', 'cancelled']);
                      });
                });
            } elseif (in_array($status, ['paid', 'unpaid'], true)) {
                $query->where('payment_status', ucfirst($status));
            } else {
                $query->whereHas('salesOrder', function ($q) use ($status) {
                    $q->where('status', $status)->orWhere('shipping_stage', $status);
                });
            }
        }

        if ($request->filled('city')) {
            $city = $request->city;
            $query->where(function ($q) use ($city) {
                $q->where('shipping_address', 'like', "%{$city}%")
                  ->orWhere('billing_address', 'like', "%{$city}%");
            });
        }

        if ($request->filled('zip')) {
            $zip = $request->zip;
            $query->where(function ($q) use ($zip) {
                $q->where('shipping_address', 'like', "%{$zip}%")
                  ->orWhere('billing_address', 'like', "%{$zip}%");
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('client_email', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('client_phone', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function salesSummary($query, $period)
    {
        $dateExpression = $period === 'month'
            ? "DATE_FORMAT(COALESCE(order_marked_at, created_at), '%Y-%m')"
            : "DATE(COALESCE(order_marked_at, created_at))";

        return (clone $query)
            ->selectRaw($dateExpression . ' as period')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(COALESCE(order_price, 0) * COALESCE(order_quantity, 0)) as sales_amount')
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit(12)
            ->get();
    }
}
