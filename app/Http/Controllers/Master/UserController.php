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
    public function index(Request $request)
    {
        $query = User::with('unit');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->input('unit_id'));
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();
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

        // Guard: Mencegah mengubah role Administrator terakhir di sistem
        if ($user->role === 'admin' && $validated['role'] !== 'admin') {
            $adminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Tidak dapat mengubah role Administrator terakhir. Sistem harus memiliki minimal 1 Administrator aktif.');
            }
        }

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

        // Guard: Mencegah menonaktifkan Administrator terakhir di sistem
        if ($user->role === 'admin' && $user->is_active) {
            $adminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan Administrator terakhir. Sistem harus memiliki minimal 1 Administrator aktif.');
            }
        }

        $user->update(['is_active' => ! $user->is_active]);
        $statusStr = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log('toggle_active', 'Pengguna', "Akun pengguna {$user->name} {$statusStr}", $user);

        return back()->with('success', "Akun pengguna {$user->name} telah {$statusStr}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Guard: Mencegah menghapus Administrator terakhir di sistem
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Tidak dapat menghapus Administrator terakhir. Sistem harus memiliki minimal 1 Administrator.');
            }
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::log('delete', 'Pengguna', "Menghapus akun pengguna {$name}", null);

        return back()->with('success', "Pengguna {$name} berhasil dihapus.");
    }
}
