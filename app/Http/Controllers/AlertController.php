<?php

namespace App\Http\Controllers;

use App\Models\SystemAlert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = SystemAlert::query();

        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->get('unread') == '1') {
            $query->whereNull('read_at');
        }

        if ($request->get('resolved') == '1') {
            $query->whereNotNull('resolved_at');
        } else {
            $query->whereNull('resolved_at');
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.alerts.index', compact('alerts'));
    }

    public function markAsRead(SystemAlert $alert)
    {
        $user = auth()->user();
        if (! $user->isAdmin() && $alert->user_id !== null && $alert->user_id !== $user->id) {
            abort(403, 'Anda tidak berhak mengakses peringatan ini.');
        }

        $alert->update(['read_at' => now()]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Peringatan ditandai sudah dibaca.');
    }

    public function markAllAsRead()
    {
        $user = auth()->user();

        $query = SystemAlert::query()->whereNull('read_at');

        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            });
        }

        $query->update(['read_at' => now()]);

        return back()->with('success', 'Seluruh peringatan ditandai sudah dibaca.');
    }
}
