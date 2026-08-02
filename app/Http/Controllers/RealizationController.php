<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRealizationRequest;
use App\Http\Requests\UpdateRealizationRequest;
use App\Models\Activity;
use App\Models\BudgetYear;
use App\Models\ExpenseType;
use App\Models\Realization;
use App\Models\Unit;
use App\Services\RealizationService;
use Illuminate\Http\Request;

class RealizationController extends Controller
{
    protected RealizationService $realizationService;

    public function __construct(RealizationService $realizationService)
    {
        $this->realizationService = $realizationService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Realization::class);

        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        $query = Realization::with(['activity.unit', 'budgetPlan', 'expenseType', 'creator']);

        if ($activeYear) {
            $query->whereHas('activity', function ($q) use ($activeYear) {
                $q->where('budget_year_id', $activeYear->id);
            });
        }

        if ($user->isPPTK()) {
            $query->whereHas('activity', function ($q) use ($user) {
                $q->where('unit_id', $user->unit_id);
            });
        } elseif ($request->filled('unit_id')) {
            $query->whereHas('activity', function ($q) use ($request) {
                $q->where('unit_id', $request->unit_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $realizations = $query->orderBy('transaction_date', 'desc')->paginate(15)->withQueryString();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $expenseTypes = ExpenseType::where('is_active', true)->orderBy('code')->get();

        return view('admin.realizations.index', compact('realizations', 'units', 'expenseTypes', 'activeYear'));
    }

    public function progress(Request $request)
    {
        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        $query = Activity::with(['unit', 'personInCharge', 'program'])
            ->whereIn('status', ['planned', 'ongoing']);

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

        $activities = $query->orderBy('start_date', 'asc')->paginate(10)->withQueryString();
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('admin.realizations.progress', compact('activities', 'units', 'activeYear'));
    }

    public function store(StoreRealizationRequest $request, Activity $activity)
    {
        $realization = $this->realizationService->storeRealization($activity, $request->validated(), auth()->user());

        return back()->with('success', "Realisasi transaksi Rp ".number_format($realization->gross_amount, 0, ',', '.')." berhasil dicatat.");
    }

    public function update(UpdateRealizationRequest $request, Realization $realization)
    {
        $updated = $this->realizationService->updateRealization($realization, $request->validated(), auth()->user());

        return back()->with('success', "Realisasi transaksi (No Bukti: {$updated->receipt_number}) berhasil diperbarui.");
    }

    public function destroy(Realization $realization)
    {
        $this->authorize('delete', $realization);
        $this->realizationService->deleteRealization($realization, auth()->user());

        return back()->with('success', 'Realisasi transaksi draft berhasil dihapus.');
    }

    public function submit(Realization $realization)
    {
        $this->authorize('submit', $realization);
        $this->realizationService->submitRealization($realization, auth()->user());

        return back()->with('success', "Realisasi transaksi (No Bukti: {$realization->receipt_number}) telah diajukan untuk verifikasi.");
    }
}
