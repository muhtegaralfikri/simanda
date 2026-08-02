<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BudgetYear;
use Illuminate\Http\Request;

class BudgetYearController extends Controller
{
    public function index()
    {
        $budgetYears = BudgetYear::orderBy('year', 'desc')->paginate(10)->withQueryString();
        return view('admin.master.budget_years.index', compact('budgetYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'unique:budget_years,year'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $budgetYear = BudgetYear::create($validated);
        ActivityLog::log('create', 'Tahun Anggaran', "Membuat Tahun Anggaran {$budgetYear->year}", $budgetYear);

        return back()->with('success', "Tahun Anggaran {$budgetYear->year} berhasil ditambahkan.");
    }

    public function update(Request $request, BudgetYear $budgetYear)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $budgetYear->update($validated);
        ActivityLog::log('update', 'Tahun Anggaran', "Memperbarui Tahun Anggaran {$budgetYear->year}", $budgetYear);

        return back()->with('success', "Tahun Anggaran {$budgetYear->year} berhasil diperbarui.");
    }

    public function toggleActive(BudgetYear $budgetYear)
    {
        // Set all other budget years to is_active = false
        BudgetYear::where('id', '!=', $budgetYear->id)->update(['is_active' => false]);
        $budgetYear->update(['is_active' => true]);

        ActivityLog::log('activate', 'Tahun Anggaran', "Mengaktifkan Tahun Anggaran {$budgetYear->year}", $budgetYear);

        return back()->with('success', "Tahun Anggaran {$budgetYear->year} sekarang aktif sebagai acuan sistem.");
    }

    public function toggleClosed(BudgetYear $budgetYear)
    {
        $budgetYear->update(['is_closed' => ! $budgetYear->is_closed]);
        $statusStr = $budgetYear->is_closed ? 'ditutup' : 'dibuka kembali';

        ActivityLog::log('toggle_closed', 'Tahun Anggaran', "Tahun Anggaran {$budgetYear->year} {$statusStr}", $budgetYear);

        return back()->with('success', "Tahun Anggaran {$budgetYear->year} telah {$statusStr}.");
    }
}
