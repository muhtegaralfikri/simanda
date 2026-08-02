<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::withCount('users')->orderBy('code')->paginate(10)->withQueryString();
        return view('admin.master.units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:units,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $unit = Unit::create($validated);
        ActivityLog::log('create', 'Unit Kerja', "Membuat Unit Kerja {$unit->name} ({$unit->code})", $unit);

        return back()->with('success', "Unit Kerja {$unit->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:units,code,'.$unit->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $unit->update($validated);
        ActivityLog::log('update', 'Unit Kerja', "Memperbarui Unit Kerja {$unit->name}", $unit);

        return back()->with('success', "Unit Kerja {$unit->name} berhasil diperbarui.");
    }

    public function toggleActive(Unit $unit)
    {
        $unit->update(['is_active' => ! $unit->is_active]);
        $statusStr = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log('toggle_active', 'Unit Kerja', "Unit Kerja {$unit->name} {$statusStr}", $unit);

        return back()->with('success', "Unit Kerja {$unit->name} telah {$statusStr}.");
    }
}
