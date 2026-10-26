<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Al Massa "Financial Reports": one page per report, a shared period filter and Excel / PDF export. */
class FinancialReportController extends Controller
{
    const PERIODS = [
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_quarter' => 'This Quarter',
        'last_quarter' => 'Last Quarter',
        'this_year' => 'This Year',
        'last_year' => 'Last Year',
        'custom' => 'Custom Dates',
    ];

    /** Extra filters some reports read from the URL (they ignore the ones they don't use). */
    const PARAMS = ['customer', 'emirate'];

    public function index(Request $request, $report = 'profit-loss')
    {
        $this->authorizeAccess();
        abort_unless(isset(FinancialReportService::REPORTS[$report]), 404);
        [$from, $to, $period] = $this->period($request);

        return view('crm.financial_reports.index', [
            'report' => (new FinancialReportService($from, $to))->withParams($request->only(self::PARAMS))->build($report),
            'reports' => FinancialReportService::REPORTS,
            'periods' => self::PERIODS,
            'period' => $period,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /** The entries behind one Profit & Loss / Expense Analysis line, each linking to its invoice or bill. */
    public function entries(Request $request)
    {
        $this->authorizeAccess();
        [$group, $category] = $this->entriesFilter($request);
        [$from, $to, $period] = $this->period($request);

        return view('crm.financial_reports.index', [
            'report' => (new FinancialReportService($from, $to))->entries($group, $category),
            'reports' => FinancialReportService::REPORTS,
            'activeTab' => $group === 'costs' ? 'expenses' : 'profit-loss',
            'formAction' => route('crm.financial_reports.entries'),
            'hidden' => array_filter(['group' => $group, 'category' => $category]),
            'periods' => self::PERIODS,
            'period' => $period,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(Request $request, $report)
    {
        $this->authorizeAccess();
        abort_unless($report === 'entries' || isset(FinancialReportService::REPORTS[$report]), 404);
        [$from, $to, $period] = $this->period($request);
        $service = (new FinancialReportService($from, $to))->withParams($request->only(self::PARAMS));
        $data = $report === 'entries' ? $service->entries(...$this->entriesFilter($request)) : $service->build($report);
        $workspace = optional($request->attributes->get('crm_workspace'));
        $meta = [
            'isAlMassa' => $workspace->slug === 'mybox-packaging-app',
            'generatedAt' => now()->format('d M Y, h:i A'),
            'rangeLabel' => $data['range_label'] ?? ($from->format('d M Y') . ' – ' . $to->format('d M Y')),
        ];
        $filename = $report . ($request->filled('customer') ? '-' . \Illuminate\Support\Str::slug($request->input('customer')) : '') . '-' . $from->format('Ymd') . '-' . $to->format('Ymd');

        if ($request->input('format') === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.financial_reports.export_pdf', ['report' => $data, 'meta' => $meta])
                ->setPaper('a4', 'landscape')
                ->download($filename . '.pdf');
        }

        return response()->view('crm.financial_reports.export_excel', ['report' => $data, 'meta' => $meta])
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.xls"');
    }

    /** @return array{0: string, 1: string} group and category for the entries page */
    protected function entriesFilter(Request $request): array
    {
        $data = $request->validate([
            'group' => 'required|in:income,cos,opex,costs',
            'category' => 'nullable|string|max:120',
        ]);

        return [$data['group'], trim((string) ($data['category'] ?? ''))];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    protected function period(Request $request): array
    {
        $period = array_key_exists($request->input('period'), self::PERIODS) ? $request->input('period') : 'this_year';
        $today = now();

        switch ($period) {
            case 'this_month': return [$today->copy()->startOfMonth(), $today->copy(), $period];
            case 'last_month': return [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth(), $period];
            case 'this_quarter': return [$today->copy()->firstOfQuarter(), $today->copy(), $period];
            case 'last_quarter': $q = $today->copy()->subQuarterNoOverflow(); return [$q->copy()->firstOfQuarter(), $q->copy()->lastOfQuarter(), $period];
            case 'last_year': return [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear(), $period];
            case 'custom':
                $request->validate(['date_from' => 'required|date', 'date_to' => 'required|date|after_or_equal:date_from']);
                return [Carbon::parse($request->input('date_from')), Carbon::parse($request->input('date_to')), $period];
            default: return [$today->copy()->startOfYear(), $today->copy(), 'this_year'];
        }
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can view financial reports.');
        }
    }
}
