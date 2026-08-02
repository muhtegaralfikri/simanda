<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\BudgetYear;
use App\Models\Realization;
use App\Models\Unit;

use App\Services\ActivityClosingService;
use App\Services\ActivitySubmissionService;
use App\Services\VerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    protected ActivitySubmissionService $submissionService;
    protected VerificationService $verificationService;
    protected ActivityClosingService $closingService;

    public function __construct(
        ActivitySubmissionService $submissionService,
        VerificationService $verificationService,
        ActivityClosingService $closingService
    ) {
        $this->submissionService = $submissionService;
        $this->verificationService = $verificationService;
        $this->closingService = $closingService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $activeYear = BudgetYear::where('is_active', true)->first();

        $query = Activity::with(['unit', 'personInCharge', 'submitter'])
            ->whereIn('status', ['waiting_verification', 'revision', 'completed']);

        if ($activeYear) {
            $query->where('budget_year_id', $activeYear->id);
        }

        if ($user->isPPTK()) {
            $query->where('unit_id', $user->unit_id);
        } elseif ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
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

        $activities = $query->orderBy('submitted_at', 'desc')->paginate(15);
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('admin.verifications.index', compact('activities', 'units', 'activeYear'));
    }

    public function show(Activity $activity)
    {
        $this->authorize('view', $activity);

        $activity->load([
            'unit',
            'personInCharge',
            'submitter',
            'budgetPlans.expenseType',
            'realizations.budgetPlan',
            'realizations.expenseType',
            'documents.documentType',
            'verifications.verifier',
            'progressLogs.updater',
        ]);

        return view('admin.verifications.show', compact('activity'));
    }

    public function submit(Activity $activity)
    {
        $this->authorize('submitForVerification', $activity);

        $this->submissionService->submitForVerification($activity, auth()->user());

        return back()->with('success', "Kegiatan {$activity->activity_code} resmi diajukan untuk verifikasi (Putaran {$activity->verification_round}).");
    }

    public function startReview(Activity $activity)
    {
        $this->authorize('startReview', $activity);

        $this->verificationService->startReview($activity, auth()->user());

        return back()->with('success', 'Pemeriksaan kegiatan dimulai.');
    }

    public function verifyRealization(Request $request, Realization $realization)
    {
        $this->authorize('verify', $realization);

        $request->validate([
            'decision' => ['required', 'in:verified,revision,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->verificationService->verifyRealization(
            $realization,
            $request->decision,
            $request->notes,
            auth()->user()
        );

        return back()->with('success', 'Keputusan verifikasi realisasi berhasil disimpan.');
    }

    public function verifyDocument(Request $request, ActivityDocument $document)
    {
        $this->authorize('verifyDocument', $document);

        $request->validate([
            'decision' => ['required', 'in:valid,revision,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->verificationService->verifyDocument(
            $document,
            $request->decision,
            $request->notes,
            auth()->user()
        );

        return back()->with('success', 'Keputusan verifikasi dokumen berhasil disimpan.');
    }

    public function requestRevision(Request $request, Activity $activity)
    {
        $this->authorize('requestRevision', $activity);

        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $this->verificationService->requestActivityRevision($activity, $request->notes, auth()->user());

        return redirect()->route('admin.verifications.index')->with('success', "Kegiatan {$activity->activity_code} telah dikembalikan untuk revisi.");
    }

    public function reject(Request $request, Activity $activity)
    {
        $this->authorize('rejectSubmission', $activity);

        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $this->verificationService->rejectActivitySubmission($activity, $request->notes, auth()->user());

        return redirect()->route('admin.verifications.index')->with('success', "Pengajuan kegiatan {$activity->activity_code} ditolak.");
    }

    public function close(Request $request, Activity $activity)
    {
        $this->authorize('closeActivity', $activity);

        $request->validate([
            'remaining_budget_note' => ['nullable', 'string', 'max:1000'],
            'closing_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->closingService->closeActivity(
            $activity,
            $request->remaining_budget_note,
            $request->closing_note,
            auth()->user()
        );

        return redirect()->route('admin.verifications.index')->with('success', "Kegiatan {$activity->activity_code} resmi disetujui dan ditutup.");
    }

    public function incoming()
    {
        return redirect()->route('admin.verifications.index', ['status' => 'waiting_verification']);
    }

    public function revisions()
    {
        return redirect()->route('admin.verifications.index', ['status' => 'revision']);
    }

    public function history()
    {
        return redirect()->route('admin.verifications.index', ['status' => 'completed']);
    }
}
