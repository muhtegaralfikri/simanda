<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\ActivityLog;
use App\Models\BudgetYear;
use App\Models\Program;
use App\Models\Unit;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Program::class);

        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        $query = Program::with(['budgetYear', 'unit'])->withCount('activities');

        if ($activeYear) {
            $query->where('budget_year_id', $activeYear->id);
        }

        // Filtering by Unit
        if ($user->isPPTK()) {
            $query->where('unit_id', $user->unit_id);
        } elseif ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('program_code', 'like', "%{$search}%")
                    ->orWhere('program_name', 'like', "%{$search}%");
            });
        }

        $programs = $query->orderBy('program_code')->paginate(10);
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('admin.programs.index', compact('programs', 'units', 'activeYear'));
    }

    public function store(StoreProgramRequest $request)
    {
        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        if (! $activeYear || $activeYear->is_closed) {
            return back()->with('error', 'Tahun anggaran tidak aktif atau telah ditutup.');
        }

        $unitId = $user->isPPTK() ? $user->unit_id : $request->unit_id;

        // Uniqueness check
        $exists = Program::where('budget_year_id', $activeYear->id)
            ->where('unit_id', $unitId)
            ->where('program_code', $request->program_code)
            ->exists();

        if ($exists) {
            return back()->withErrors(['program_code' => 'Kode program sudah ada pada unit kerja dan tahun anggaran ini.']);
        }

        $program = Program::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $unitId,
            'program_code' => $request->program_code,
            'program_name' => $request->program_name,
            'description' => $request->description,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        ActivityLog::log('create', 'Program', "Membuat Program {$program->program_code} - {$program->program_name}", $program);

        return back()->with('success', "Program {$program->program_name} berhasil ditambahkan.");
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $user = auth()->user();
        $unitId = $user->isPPTK() ? $user->unit_id : $request->unit_id;

        // Uniqueness check
        $exists = Program::where('budget_year_id', $program->budget_year_id)
            ->where('unit_id', $unitId)
            ->where('program_code', $request->program_code)
            ->where('id', '!=', $program->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['program_code' => 'Kode program sudah ada pada unit kerja ini.']);
        }

        $program->update([
            'unit_id' => $unitId,
            'program_code' => $request->program_code,
            'program_name' => $request->program_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $program->is_active,
            'updated_by' => $user->id,
        ]);

        ActivityLog::log('update', 'Program', "Memperbarui Program {$program->program_code}", $program);

        return back()->with('success', "Program {$program->program_name} berhasil diperbarui.");
    }

    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);

        if ($program->activities()->exists()) {
            return back()->with('error', 'Program yang sudah digunakan oleh kegiatan tidak dapat dihapus.');
        }

        $code = $program->program_code;
        $program->delete();

        ActivityLog::log('delete', 'Program', "Menghapus Program {$code}");

        return back()->with('success', "Program {$code} berhasil dihapus.");
    }
}
