<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when(request('role'), fn ($q, $role) => $q->where('role', $role))
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        }

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'password_hash' => $request->password,
            'role' => $request->role,
            'status' => $request->status,
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Akun pengguna berhasil dibuat.',
                'data' => $user,
            ], 201);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->only(['full_name', 'username', 'role', 'status']);

        if ($request->filled('password')) {
            $data['password_hash'] = $request->password;
        }

        $user->update($data);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Akun pengguna berhasil diperbarui.',
                'data' => $user->fresh(),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Akun pengguna berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dihapus.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorize('update', $user);

        $user->update(['status' => $user->status === 'active' ? 'blocked' : 'active']);

        $label = $user->status === 'active' ? 'diaktifkan' : 'diblokir';

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Akun {$user->full_name} berhasil {$label}.",
                'data' => $user->fresh(),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->full_name} berhasil {$label}.");
    }
}
