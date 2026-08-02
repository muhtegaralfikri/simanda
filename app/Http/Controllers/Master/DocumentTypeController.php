<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTypeController extends Controller
{
    public function index()
    {
        $documentTypes = DocumentType::orderBy('stage')->orderBy('code')->paginate(10)->withQueryString();
        return view('admin.master.document_types.index', compact('documentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'stage' => ['required', Rule::in(['planning', 'execution', 'financial'])],
            'is_required' => ['boolean'],
            'allowed_extensions' => ['required', 'string', 'max:255'],
            'maximum_size' => ['required', 'integer', 'min:512', 'max:51200'],
        ]);

        $dt = DocumentType::create($validated);
        ActivityLog::log('create', 'Jenis Dokumen', "Membuat Jenis Dokumen {$dt->name}", $dt);

        return back()->with('success', "Jenis Dokumen {$dt->name} berhasil ditambahkan.");
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code,'.$documentType->id],
            'name' => ['required', 'string', 'max:255'],
            'stage' => ['required', Rule::in(['planning', 'execution', 'financial'])],
            'is_required' => ['boolean'],
            'allowed_extensions' => ['required', 'string', 'max:255'],
            'maximum_size' => ['required', 'integer', 'min:512', 'max:51200'],
        ]);

        $documentType->update($validated);
        ActivityLog::log('update', 'Jenis Dokumen', "Memperbarui Jenis Dokumen {$documentType->name}", $documentType);

        return back()->with('success', "Jenis Dokumen {$documentType->name} berhasil diperbarui.");
    }

    public function toggleActive(DocumentType $documentType)
    {
        $documentType->update(['is_active' => ! $documentType->is_active]);
        $statusStr = $documentType->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log('toggle_active', 'Jenis Dokumen', "Jenis Dokumen {$documentType->name} {$statusStr}", $documentType);

        return back()->with('success', "Jenis Dokumen {$documentType->name} telah {$statusStr}.");
    }
}
