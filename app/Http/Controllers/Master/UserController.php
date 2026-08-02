<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('unit')->orderBy('name')->paginate(10);
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('admin.master.users.index', compact('users', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['admin', 'pimpinan', 'pptk', 'verifier'])],
            'unit_id' => ['nullable', 'exists:units,id'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        ActivityLog::log('create', 'Pengguna', "Membuat akun pengguna {$user->name} ({$user->role})", $user);

        return back()->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['admin', 'pimpinan', 'pptk', 'verifier'])],
            'unit_id' => ['nullable', 'exists:units,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        ActivityLog::log('update', 'Pengguna', "Memperbarui akun pengguna {$user->name}", $user);

        return back()->with('success', "Pengguna {$user->name} berhasil diperbarui.");
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $statusStr = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log('toggle_active', 'Pengguna', "Akun pengguna {$user->name} {$statusStr}", $user);

        return back()->with('success', "Akun pengguna {$user->name} telah {$statusStr}.");
    }
}
