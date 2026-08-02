<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\BudgetYear;
use App\Models\ExpenseType;
use App\Models\FundingSource;
use App\Models\Program;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityPlanningService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    protected ActivityPlanningService $planningService;

    public function __construct(ActivityPlanningService $planningService)
    {
        $this->planningService = $planningService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Activity::class);

        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        $query = Activity::with(['budgetYear', 'unit', 'program', 'personInCharge', 'fundingSource'])
            ->withSum('budgetPlans as total_rab', 'total');

        if ($activeYear) {
            $query->where('budget_year_id', $activeYear->id);
        }

        // Filtering based on role & unit
        if ($user->isPPTK()) {
            $query->where('unit_id', $user->unit_id);
        } elseif ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('funding_source_id')) {
            $query->where('funding_source_id', $request->funding_source_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('activity_code', 'like', "%{$search}%")
                    ->orWhere('activity_name', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('program_name')->get();
        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();

        return view('admin.activities.index', compact('activities', 'units', 'programs', 'fundingSources', 'activeYear'));
    }

    public function create()
    {
        $this->authorize('create', Activity::class);

        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        if (! $activeYear || $activeYear->is_closed) {
            return redirect()->route('activities.index')->with('error', 'Tahun anggaran sedang tidak aktif atau ditutup.');
        }

        $units = $user->isPPTK() ? Unit::where('id', $user->unit_id)->get() : Unit::where('is_active', true)->orderBy('name')->get();
        
        $programs = Program::where('is_active', true)
            ->where('budget_year_id', $activeYear->id)
            ->when($user->isPPTK(), fn($q) => $q->where('unit_id', $user->unit_id))
            ->orderBy('program_name')->get();

        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();

        $pptkUsers = User::where('role', 'pptk')
            ->where('is_active', true)
            ->when($user->isPPTK(), fn($q) => $q->where('unit_id', $user->unit_id))
            ->orderBy('name')->get();

        return view('admin.activities.create', compact('units', 'programs', 'fundingSources', 'pptkUsers', 'activeYear'));
    }

    public function store(StoreActivityRequest $request)
    {
        $user = auth()->user();
        $activity = $this->planningService->createActivity($user, $request->validated());

        return redirect()->route('activities.show', $activity->id)
            ->with('success', "Kegiatan {$activity->activity_name} berhasil dibuat. Silakan tambahkan rincian RAB.");
    }

    public function show(Activity $activity)
    {
        $this->authorize('view', $activity);

        $activity->load(['budgetYear', 'unit', 'program', 'personInCharge', 'fundingSource', 'budgetPlans.expenseType']);
        $expenseTypes = ExpenseType::where('is_active', true)->orderBy('code')->get();

        return view('admin.activities.show', compact('activity', 'expenseTypes'));
    }

    public function edit(Activity $activity)
    {
        $this->authorize('update', $activity);

        $user = auth()->user();
        $activeYear = $activity->budgetYear;

        $units = $user->isPPTK() ? Unit::where('id', $user->unit_id)->get() : Unit::where('is_active', true)->orderBy('name')->get();
        
        $programs = Program::where('is_active', true)
            ->where('budget_year_id', $activeYear->id)
            ->where('unit_id', $activity->unit_id)
            ->orderBy('program_name')->get();

        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();

        $pptkUsers = User::where('role', 'pptk')
            ->where('is_active', true)
            ->where('unit_id', $activity->unit_id)
            ->orderBy('name')->get();

        return view('admin.activities.edit', compact('activity', 'units', 'programs', 'fundingSources', 'pptkUsers', 'activeYear'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        $user = auth()->user();
        $updated = $this->planningService->updateActivity($user, $activity, $request->validated());

        return redirect()->route('activities.show', $updated->id)
            ->with('success', "Kegiatan {$updated->activity_name} berhasil diperbarui.");
    }

    public function destroy(Activity $activity)
    {
        $this->authorize('delete', $activity);

        $code = $activity->activity_code;
        $activity->delete();

        return redirect()->route('activities.index')->with('success', "Kegiatan {$code} berhasil dihapus.");
    }

    public function setPlanned(Activity $activity)
    {
        $this->authorize('changeStatus', $activity);
        $this->planningService->setPlanned($activity);

        return back()->with('success', "Kegiatan {$activity->activity_code} telah resmi ditetapkan sebagai Direncanakan.");
    }

    public function returnToDraft(Activity $activity)
    {
        $this->authorize('changeStatus', $activity);
        $this->planningService->returnToDraft($activity);

        return back()->with('success', "Kegiatan {$activity->activity_code} dikembalikan menjadi status Draft.");
    }

    public function cancel(Request $request, Activity $activity)
    {
        $this->authorize('changeStatus', $activity);

        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        $this->planningService->cancelActivity($activity, $request->cancellation_reason);

        return back()->with('success', "Kegiatan {$activity->activity_code} telah dibatalkan.");
    }
}
