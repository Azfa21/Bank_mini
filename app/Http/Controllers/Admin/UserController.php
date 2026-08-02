<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->when(request('role'), fn ($q, $role) => $q->where('role', $role))
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'password_hash' => $request->password,
            'role' => $request->role,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->only(['full_name', 'username', 'role', 'status']);

        if ($request->filled('password')) {
            $data['password_hash'] = $request->password;
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dihapus.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $user->update(['status' => $user->status === 'active' ? 'blocked' : 'active']);

        $label = $user->status === 'active' ? 'diaktifkan' : 'diblokir';

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->full_name} berhasil {$label}.");
    }
}
