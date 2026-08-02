<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;

class SystemHealthController extends Controller
{
    protected SystemHealthService $healthService;

    public function __construct(SystemHealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Halaman status sistem hanya dapat diakses oleh Administrator.');
        }

        $health = $this->healthService->getHealthStatus();

        return view('admin.system.health', compact('health'));
    }
}
