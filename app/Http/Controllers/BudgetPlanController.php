<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetPlanRequest;
use App\Http\Requests\UpdateBudgetPlanRequest;
use App\Models\Activity;
use App\Models\BudgetPlan;
use App\Models\BudgetYear;
use App\Models\Unit;
use App\Services\BudgetPlanService;
use Illuminate\Http\Request;

class BudgetPlanController extends Controller
{
    protected BudgetPlanService $budgetPlanService;

    public function __construct(BudgetPlanService $budgetPlanService)
    {
        $this->budgetPlanService = $budgetPlanService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        $query = Activity::with(['unit', 'program', 'personInCharge', 'budgetPlans.expenseType']);

        if ($activeYear) {
            $query->where('budget_year_id', $activeYear->id);
        }

        if ($user->isPPTK()) {
            $query->where('unit_id', $user->unit_id);
        } elseif ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('activity_code', 'like', "%{$search}%")
                    ->orWhere('activity_name', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderBy('created_at', 'desc')->paginate(15);
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('admin.budget_plans.index', compact('activities', 'units', 'activeYear'));
    }

    public function store(StoreBudgetPlanRequest $request, Activity $activity)
    {
        $this->budgetPlanService->storeBudgetPlan($activity, $request->validated());

        return back()->with('success', 'Rincian RAB berhasil ditambahkan.');
    }

    public function update(UpdateBudgetPlanRequest $request, BudgetPlan $budgetPlan)
    {
        $this->budgetPlanService->updateBudgetPlan($budgetPlan, $request->validated());

        return back()->with('success', 'Rincian RAB berhasil diperbarui.');
    }

    public function destroy(BudgetPlan $budgetPlan)
    {
        $this->authorize('delete', $budgetPlan);
        $this->budgetPlanService->deleteBudgetPlan($budgetPlan);

        return back()->with('success', 'Rincian RAB berhasil dihapus.');
    }
}
