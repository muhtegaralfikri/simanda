<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExpenseType;
use Illuminate\Http\Request;

class ExpenseTypeController extends Controller
{
    public function index()
    {
        $expenseTypes = ExpenseType::orderBy('code')->paginate(10);
        return view('admin.master.expense_types.index', compact('expenseTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:expense_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $et = ExpenseType::create($validated);
        ActivityLog::log('create', 'Jenis Belanja', "Membuat Jenis Belanja {$et->name}", $et);

        return back()->with('success', "Jenis Belanja {$et->name} berhasil ditambahkan.");
    }

    public function update(Request $request, ExpenseType $expenseType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:expense_types,code,'.$expenseType->id],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $expenseType->update($validated);
        ActivityLog::log('update', 'Jenis Belanja', "Memperbarui Jenis Belanja {$expenseType->name}", $expenseType);

        return back()->with('success', "Jenis Belanja {$expenseType->name} berhasil diperbarui.");
    }

    public function toggleActive(ExpenseType $expenseType)
    {
        $expenseType->update(['is_active' => ! $expenseType->is_active]);
        $statusStr = $expenseType->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log('toggle_active', 'Jenis Belanja', "Jenis Belanja {$expenseType->name} {$statusStr}", $expenseType);

        return back()->with('success', "Jenis Belanja {$expenseType->name} telah {$statusStr}.");
    }
}
