<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartActivityRequest;
use App\Http\Requests\UpdateActivityProgressRequest;
use App\Models\Activity;
use App\Services\ActivityExecutionService;

class ActivityExecutionController extends Controller
{
    protected ActivityExecutionService $executionService;

    public function __construct(ActivityExecutionService $executionService)
    {
        $this->executionService = $executionService;
    }

    public function start(StartActivityRequest $request, Activity $activity)
    {
        $this->executionService->startExecution($activity, auth()->user());

        return back()->with('success', "Pelaksanaan kegiatan {$activity->activity_code} resmi dimulai.");
    }

    public function updateProgress(UpdateActivityProgressRequest $request, Activity $activity)
    {
        $this->executionService->updateProgress(
            $activity,
            $request->progress_percentage,
            $request->note,
            auth()->user()
        );

        return back()->with('success', "Progres kegiatan berhasil diperbarui menjadi {$request->progress_percentage}%.");
    }
}
