<?php

namespace App\Http\Controllers;

use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\Program;
use App\Models\Unit;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardAnalyticsService $analyticsService;

    public function __construct(DashboardAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $filters = $request->only([
            'budget_year_id',
            'unit_id',
            'program_id',
            'funding_source_id',
            'status',
            'person_in_charge_id',
            'start_date',
            'end_date',
        ]);

        $analytics = $this->analyticsService->getAnalytics($filters, $user);

        $budgetYears = BudgetYear::orderBy('year', 'desc')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('program_name')->get();
        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();
        $pptks = User::where('role', 'pptk')->orderBy('name')->get();

        return view('admin.dashboard.index', compact(
            'analytics',
            'budgetYears',
            'units',
            'programs',
            'fundingSources',
            'pptks'
        ));
    }
}
