<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FundingSource;
use Illuminate\Http\Request;

class FundingSourceController extends Controller
{
    public function index()
    {
        $fundingSources = FundingSource::orderBy('code')->paginate(10);
        return view('admin.master.funding_sources.index', compact('fundingSources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:funding_sources,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $fs = FundingSource::create($validated);
        ActivityLog::log('create', 'Sumber Dana', "Membuat Sumber Dana {$fs->name}", $fs);

        return back()->with('success', "Sumber Dana {$fs->name} berhasil ditambahkan.");
    }

    public function update(Request $request, FundingSource $fundingSource)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:funding_sources,code,'.$fundingSource->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $fundingSource->update($validated);
        ActivityLog::log('update', 'Sumber Dana', "Memperbarui Sumber Dana {$fundingSource->name}", $fundingSource);

        return back()->with('success', "Sumber Dana {$fundingSource->name} berhasil diperbarui.");
    }

    public function toggleActive(FundingSource $fundingSource)
    {
        $fundingSource->update(['is_active' => ! $fundingSource->is_active]);
        $statusStr = $fundingSource->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log('toggle_active', 'Sumber Dana', "Sumber Dana {$fundingSource->name} {$statusStr}", $fundingSource);

        return back()->with('success', "Sumber Dana {$fundingSource->name} telah {$statusStr}.");
    }
}
