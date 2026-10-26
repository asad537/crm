<?php

namespace App\Http\Controllers\Crm;

use App\CrmBudget;
use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Al Massa budget: a yearly amount per Profit & Loss category, compared in the Budget vs Actual report. */
class BudgetController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorizeAccess();
        $year = $this->year($request);

        // Every category the books have used (all years) plus any already budgeted, with last year's actual for reference.
        $allTime = (new FinancialReportService(Carbon::create(2000, 1, 1), now()))->plTotalsFor(Carbon::create(2000, 1, 1), now());
        $lastYear = (new FinancialReportService(Carbon::create($year - 1, 1, 1), Carbon::create($year - 1, 12, 31)))
            ->plTotalsFor(Carbon::create($year - 1, 1, 1), Carbon::create($year - 1, 12, 31)->endOfDay());
        $saved = CrmBudget::where('year', $year)->get()->groupBy('group')->map(fn ($g) => $g->pluck('amount', 'category'));

        $groups = [];
        foreach (CrmBudget::GROUPS as $group => $title) {
            $categories = collect(array_keys($allTime[$group]))->merge(($saved[$group] ?? collect())->keys())->unique()->sort()->values();
            $groups[$group] = ['title' => $title, 'rows' => $categories->map(fn ($c) => [
                'category' => $c,
                'budget' => isset($saved[$group][$c]) ? (float) $saved[$group][$c] : null,
                'last_year' => (float) ($lastYear[$group][$c] ?? 0),
            ])->all()];
        }

        return view('crm.budgets.edit', ['year' => $year, 'groups' => $groups]);
    }

    public function update(Request $request)
    {
        $this->authorizeAccess();
        $year = $this->year($request);
        $data = $request->validate([
            'budget' => 'array',
            'budget.*' => 'array',
            'budget.*.*' => 'nullable|numeric|min:0|max:9999999999',
            'new_group' => 'nullable|in:income,cos,opex',
            'new_category' => 'nullable|string|max:120',
            'new_amount' => 'nullable|numeric|min:0|max:9999999999',
        ]);

        $rows = $data['budget'] ?? [];
        if (!empty($data['new_category']) && !empty($data['new_group'])) {
            $rows[$data['new_group']][trim($data['new_category'])] = $data['new_amount'] ?? 0;
        }
        $saved = 0;
        foreach ($rows as $group => $categories) {
            if (!isset(CrmBudget::GROUPS[$group])) continue;
            foreach ($categories as $category => $amount) {
                $category = trim((string) $category);
                if ($category === '') continue;
                if ($amount === null || $amount === '' || (float) $amount <= 0) {
                    CrmBudget::where(['year' => $year, 'group' => $group, 'category' => $category])->delete();
                    continue;
                }
                CrmBudget::updateOrCreate(
                    ['workspace_id' => \App\Support\CrmWorkspaceContext::id(), 'year' => $year, 'group' => $group, 'category' => $category],
                    ['amount' => round((float) $amount, 2), 'updated_by' => Auth::guard('crm')->id()]
                );
                $saved++;
            }
        }

        return redirect()->route('crm.budgets.edit', ['year' => $year])->with('success', 'Budget ' . $year . ' saved (' . $saved . ' categor' . ($saved === 1 ? 'y' : 'ies') . ').');
    }

    protected function year(Request $request): int
    {
        $year = (int) $request->input('year', now()->year);

        return $year >= 2000 && $year <= now()->year + 5 ? $year : now()->year;
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::guard('crm')->user();
        abort_unless($user->isAdmin() || $user->isAccounts(), 403, 'Only admins and accounts can set the budget.');
    }
}
