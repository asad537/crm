<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmployeeAdvance;
use App\CrmExpense;
use App\CrmPayrollLine;
use App\CrmPurchaseInvoice;
use App\CrmUser;
use App\DemandRequestPayment;
use App\Http\Controllers\Controller;
use App\VendorPurchase;
use App\VendorPurchasePayment;
use App\Support\CrmWorkspaceContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Al Massa "Payments": every payment the company made — vendor payments, demand-request expenses,
 * expense payments entered with "Make Payment" here, every payment against a purchase invoice, salaries marked
 * paid and salary advances given (an advance is cash out when given; later salaries are paid net of it).
 * Excluded: payments of deleted purchases/demands, and demand settlements (account funding is a transfer,
 * not an expense). A demand payment whose vendor invoice # matches a paid vendor purchase is the same
 * spend recorded twice: it is listed but counted once (under Vendors).
 */
class PaymentController extends Controller
{
    const SOURCES = [
        'vendor' => 'Vendor Payment',
        'direct' => 'Expense · Company',
        'account' => 'Expense · Accountant',
        'expense' => 'Expense Payment',
        'purchase' => 'Purchase Invoice',
        'salary' => 'Salary',
        'advance' => 'Salary Advance',
    ];

    public function index(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can view payments.');
        }

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
            'source' => array_key_exists($request->input('source'), self::SOURCES) ? $request->input('source') : null,
            'method' => $request->input('method') ? trim((string) $request->input('method')) : null,
            'party' => $request->input('party') ? trim((string) $request->input('party')) : null,
        ];

        $all = $this->payments();
        $payments = $this->applyFilters($all, $filters);
        $counted = $payments->whereNull('duplicate_of');
        $allCounted = $all->whereNull('duplicate_of');
        $monthStart = now()->startOfMonth();
        $thisMonth = $allCounted->filter(fn ($p) => $p->date->gte($monthStart));
        $duplicates = $payments->whereNotNull('duplicate_of');

        $totals = [
            'paid' => (float) $counted->sum('amount'),
            'count' => $counted->count(),
            'month' => (float) $thisMonth->sum('amount'),
            'month_count' => $thisMonth->count(),
            'vendor' => (float) $counted->where('source', 'vendor')->sum('amount'),
            'expense' => (float) $counted->whereIn('source', ['direct', 'account', 'expense'])->sum('amount'),
            'purchase' => (float) $counted->where('source', 'purchase')->sum('amount'),
            'salary' => (float) $counted->whereIn('source', ['salary', 'advance'])->sum('amount'),
            'duplicate_count' => $duplicates->count(),
            'duplicate_amount' => (float) $duplicates->sum('amount'),
        ];
        $methodOptions = $all->pluck('method')->filter()->unique()->sort()->values();
        $partyOptions = $all->pluck('party')->filter(fn ($p) => $p !== '—')->unique()->sort()->values();

        $perPage = 25;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $entries = new \Illuminate\Pagination\LengthAwarePaginator(
            $payments->forPage($page, $perPage)->values(),
            $payments->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('crm.payments.index', [
            'entries' => $entries,
            'totals' => $totals,
            'filters' => $filters,
            'sources' => self::SOURCES,
            'methodOptions' => $methodOptions,
            'partyOptions' => $partyOptions,
        ] + self::makePaymentOptions());
    }

    /** Lists the "Make Payment" dialog needs (crm.payments._make_payment). */
    public static function makePaymentOptions(): array
    {
        return [
            'categoryOptions' => collect(CrmExpense::CATEGORIES)->concat(CrmExpense::distinct()->orderBy('category')->pluck('category'))->unique()->values(),
            'paidFromOptions' => CrmExpense::PAID_FROM,
            'payeeTypes' => CrmExpense::PAYEE_TYPES,
            'supplierNames' => \App\Vendor::orderBy('name')->pluck('name')->unique()->values(),
            'customerNames' => \App\CrmCustomer::orderBy('name')->pluck('name')->unique()->values(),
        ];
    }

    /** Every outgoing payment in the active workspace as normalized rows, newest first (also used by Financial Reports). */
    public function payments()
    {
        $rows = collect();
        $userNames = CrmUser::pluck('name', 'id');

        // 1) Vendor payments: against a purchase, or on the vendor's account (no purchase).
        $vendorPayments = VendorPurchasePayment::with(['purchase', 'vendor'])
            ->where('workspace_id', CrmWorkspaceContext::id())
            ->get()
            ->filter(fn ($p) => $p->vendor_purchase_id ? $p->purchase : $p->vendor);
        foreach ($vendorPayments as $payment) {
            $purchase = $payment->purchase;
            $vendorName = $purchase ? ($purchase->vendor_name ?: optional($payment->vendor)->name) : optional($payment->vendor)->name;
            $vendorId = $purchase ? $purchase->vendor_id : $payment->vendor_id;
            $rows->push($this->row([
                'date' => $payment->paid_at ?: $payment->created_at,
                'source' => 'vendor',
                'party' => $vendorName,
                'ref' => $purchase ? ($purchase->invoice_number ?: 'Purchase #'.$purchase->id) : 'On account',
                'detail' => $purchase ? ($purchase->item_name ?: $purchase->category) : $payment->note,
                'category' => $purchase ? ($purchase->expense_type ?: $purchase->category) : null,
                'method' => $payment->method,
                'note' => $payment->note,
                'paid_by' => $userNames[$payment->created_by] ?? null,
                'amount' => $payment->amount,
                'currency' => $purchase ? $purchase->currency : null,
                'url' => $vendorId ? route('crm.vendor_purchases.index', ['vendor_id' => $vendorId]) : route('crm.vendor_purchases.index'),
                'proof_url' => $payment->receipt_url,
                'sort_id' => $payment->id,
                'link_type' => 'vendor_payment', 'link_id' => $payment->id, 'bank_account_id' => $payment->bank_account_id,
            ]));
        }

        // Paid vendor purchases by invoice # — a demand payment for the same invoice is the same spend.
        $paidInvoices = VendorPurchase::where('paid_amount', '>', 0)
            ->whereNotNull('invoice_number')->where('invoice_number', '!=', '')
            ->pluck('invoice_number')
            ->mapWithKeys(fn ($no) => [mb_strtolower(trim($no)) => $no]);

        // 2) Demand-request expenses (paid directly by the company, or by the accountant).
        $demandPayments = DemandRequestPayment::with(['request', 'item', 'files'])
            ->whereHas('request')
            ->get();
        foreach ($demandPayments as $payment) {
            $demand = $payment->request;
            $item = $payment->item;
            $invoiceNo = trim((string) ($payment->vendor_invoice_no ?: optional($item)->vendor_invoice_no));
            $proofs = $payment->allProofs();
            $method = $payment->adjust_from ? 'Adjustment · '.$payment->adjust_from : $payment->method;
            $rows->push($this->row([
                'date' => $payment->paid_at ?: $payment->created_at,
                'source' => $payment->pay_type === 'Direct' ? 'direct' : 'account',
                'party' => $payment->paid_to ?: optional($item)->vendor_name,
                'ref' => 'Demand #'.str_pad($demand->request_no, 3, '0', STR_PAD_LEFT).($invoiceNo !== '' ? ' · Inv '.$invoiceNo : ''),
                'detail' => $payment->note ?: optional($item)->description,
                'category' => $payment->category ?: optional($item)->category,
                'method' => $method,
                'paid_by' => $userNames[$payment->created_by] ?? null,
                'amount' => $payment->amount,
                'url' => route('crm.demand_requests.show', $demand->id),
                'proof_url' => $proofs[0]['url'] ?? null,
                'proof_count' => count($proofs),
                'duplicate_of' => $invoiceNo !== '' ? ($paidInvoices[mb_strtolower($invoiceNo)] ?? null) : null,
                'sort_id' => $payment->id,
                'link_type' => 'demand_payment', 'link_id' => $payment->id, 'bank_account_id' => $payment->bank_account_id,
            ]));
        }

        // 3) Expense payments made with "Make Payment" on this page.
        foreach (CrmExpense::all() as $expense) {
            $rows->push($this->row([
                'date' => $expense->expense_date,
                'source' => 'expense',
                'party' => $expense->payee_name ?: $expense->payee_type_label,
                'ref' => $expense->number,
                'detail' => $expense->name,
                'category' => $expense->category,
                'method' => $expense->paid_from,
                'note' => trim($expense->reference.' '.$expense->note),
                'paid_by' => $userNames[$expense->created_by] ?? null,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                'url' => route('crm.expenses.show', $expense->id),
                'proof_url' => $expense->proof_path ? route('crm.expenses.proof', $expense->id) : null,
                'proof_count' => $expense->proof_path ? 1 : 0,
                'sort_id' => $expense->id,
                'link_type' => 'expense', 'link_id' => $expense->id, 'bank_account_id' => $expense->bank_account_id,
                'form' => $expense->formData(),
            ]));
        }

        // 4) Purchase invoice payments: one row per payment (part payments too), with what is due on the invoice now.
        $invoices = CrmPurchaseInvoice::with(['items', 'payments'])->get();
        foreach ($invoices as $invoice) {
            $dueNow = round((float) $invoice->total - (float) $invoice->payments->sum('amount'), 2);
            foreach ($invoice->payments as $payment) {
                $rows->push($this->row([
                    'date' => $payment->paid_at,
                    'source' => 'purchase',
                    'party' => $invoice->vendor_name,
                    'ref' => $invoice->number.($invoice->reference ? ' · '.$invoice->reference : ''),
                    'detail' => $invoice->description ?: $invoice->items->pluck('name')->implode(', '),
                    'category' => $invoice->items->pluck('category')->filter()->unique()->implode(', ') ?: $invoice->project,
                    'method' => $payment->method,
                    'note' => trim($payment->reference.' '.$payment->note),
                    'paid_by' => $userNames[$payment->created_by] ?? null,
                    'amount' => $payment->amount,
                    'currency' => $invoice->currency,
                    'url' => route('crm.purchase_invoices.show', $invoice->id),
                    'proof_url' => $invoice->attachment_path ? route('crm.purchase_invoices.attachment', $invoice->id) : null,
                    'proof_count' => $invoice->attachment_path ? 1 : 0,
                    'sort_id' => $payment->id,
                    'link_type' => 'invoice_payment', 'link_id' => $payment->id, 'bank_account_id' => $payment->bank_account_id,
                    // Same figures as the invoice page: total, and what is still due on it today.
                    'invoice_status' => 'Invoice '.number_format((float) $invoice->total, 2).' · '.($dueNow > 0.009
                        ? 'Balance due '.number_format($dueNow, 2)
                        : ($dueNow < -0.009 ? 'Fully paid + advance '.number_format(-$dueNow, 2) : 'Fully paid')),
                    'invoice_due' => max(0, $dueNow),
                ]));
            }
        }

        // 5) Salaries marked paid on a salary sheet (net amount actually handed over).
        $salaryLines = CrmPayrollLine::with('run')->whereNotNull('paid_at')
            ->whereHas('run', fn ($q) => $q->where('workspace_id', CrmWorkspaceContext::id()))->get();
        foreach ($salaryLines as $line) {
            $rows->push($this->row([
                'date' => $line->paid_at,
                'source' => 'salary',
                'party' => $line->employee_name,
                'ref' => 'Salary '.$line->run->periodStart()->format('M Y'),
                'detail' => trim(($line->designation ?: 'Salary').($line->advance_deduction > 0 ? ' · advance cut '.number_format((float) $line->advance_deduction, 2) : '')),
                'category' => $line->department,
                'method' => $line->paid_method,
                'note' => $line->paid_reference,
                'paid_by' => $userNames[$line->paid_by] ?? null,
                'amount' => $line->net,
                'url' => route('crm.payroll.show', $line->payroll_run_id),
                'sort_id' => $line->id,
                'link_type' => 'payroll_line', 'link_id' => $line->id, 'bank_account_id' => $line->bank_account_id,
            ]));
        }

        // 6) Salary advances given to employees.
        foreach (CrmEmployeeAdvance::with('employee')->get() as $advance) {
            $rows->push($this->row([
                'date' => $advance->given_date,
                'source' => 'advance',
                'party' => optional($advance->employee)->name,
                'ref' => 'Advance',
                'detail' => $advance->note ?: 'Salary advance',
                'category' => optional($advance->employee)->department,
                'method' => $advance->method,
                'paid_by' => $userNames[$advance->created_by] ?? null,
                'amount' => $advance->amount,
                'url' => route('crm.employees.index', ['tab' => 'advances']),
                'sort_id' => $advance->id,
                'link_type' => 'advance', 'link_id' => $advance->id, 'bank_account_id' => $advance->bank_account_id,
            ]));
        }

        return $rows->sort(function ($a, $b) {
            return [$b->date->timestamp, $b->sort_id] <=> [$a->date->timestamp, $a->sort_id];
        })->values();
    }

    protected function row(array $data)
    {
        return (object) array_merge([
            'category' => null, 'method' => null, 'note' => null, 'paid_by' => null, 'url' => null,
            'proof_url' => null, 'proof_count' => 0, 'duplicate_of' => null, 'sort_id' => 0,
            'link_type' => null, 'link_id' => null, 'bank_account_id' => null, 'form' => null, 'invoice_status' => null, 'invoice_due' => null,
        ], $data, [
            'date' => Carbon::parse($data['date'])->startOfDay(),
            'source_label' => self::SOURCES[$data['source']],
            'party' => $data['party'] ?: '—',
            'amount' => round((float) $data['amount'], 2),
            'currency' => ($data['currency'] ?? null) ?: 'AED',
        ]);
    }

    protected function applyFilters($rows, array $filters)
    {
        if ($filters['date_from']) {
            $from = Carbon::parse($filters['date_from'])->startOfDay();
            $rows = $rows->filter(fn ($r) => $r->date->gte($from));
        }
        if ($filters['date_to']) {
            $to = Carbon::parse($filters['date_to'])->endOfDay();
            $rows = $rows->filter(fn ($r) => $r->date->lte($to));
        }
        if ($filters['source']) {
            $rows = $rows->where('source', $filters['source']);
        }
        if ($filters['method']) {
            $rows = $rows->filter(fn ($r) => strcasecmp((string) $r->method, $filters['method']) === 0);
        }
        if ($filters['party']) {
            $rows = $rows->where('party', $filters['party']);
        }
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $rows = $rows->filter(function ($r) use ($search) {
                foreach (['party', 'ref', 'detail', 'category', 'method', 'note'] as $field) {
                    if (stripos((string) $r->$field, $search) !== false) return true;
                }
                return false;
            });
        }

        return $rows->values();
    }
}
