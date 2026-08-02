<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->has('module') && $request->module != '') {
            $query->where('module', $request->module);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.system.logs.index', compact('logs'));
    }
}
