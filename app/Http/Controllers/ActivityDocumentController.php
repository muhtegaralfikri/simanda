<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityDocumentRequest;
use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\BudgetYear;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Services\ActivityDocumentService;
use Illuminate\Http\Request;

class ActivityDocumentController extends Controller
{
    protected ActivityDocumentService $documentService;

    public function __construct(ActivityDocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ActivityDocument::class);

        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        $query = Activity::with(['unit', 'personInCharge', 'documents.documentType'])
            ->where('status', '!=', 'draft');

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

        $activities = $query->orderBy('created_at', 'desc')->paginate(10);
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $documentTypes = DocumentType::where('is_active', true)->orderBy('stage')->get();

        return view('admin.documents.index', compact('activities', 'units', 'documentTypes', 'activeYear'));
    }

    public function store(StoreActivityDocumentRequest $request, Activity $activity)
    {
        $doc = $this->documentService->uploadDocument(
            $activity,
            $request->file('file'),
            $request->document_type_id,
            $request->realization_id,
            auth()->user()
        );

        return back()->with('success', "Dokumen '{$doc->original_name}' (Versi {$doc->version}) berhasil diunggah.");
    }

    public function replace(Request $request, ActivityDocument $document)
    {
        $this->authorize('uploadDocument', $document->activity);

        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $newDoc = $this->documentService->replaceDocument($document, $request->file('file'), auth()->user());

        return back()->with('success', "Dokumen berhasil diperbarui menjadi Versi {$newDoc->version} ('{$newDoc->original_name}').");
    }

    public function destroy(ActivityDocument $document)
    {
        $this->authorize('deleteDocument', $document);
        $this->documentService->deleteDocument($document, auth()->user());

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function download(ActivityDocument $document)
    {
        $this->authorize('downloadDocument', $document);

        return $this->documentService->downloadDocument($document, auth()->user(), false);
    }

    public function preview(ActivityDocument $document)
    {
        $this->authorize('downloadDocument', $document);

        return $this->documentService->downloadDocument($document, auth()->user(), true);
    }
}
