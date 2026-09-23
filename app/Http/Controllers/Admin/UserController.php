<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\CashTransfer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    private const ROLES = ['admin', 'supervisor', 'teller'];

    private const ROLE_LABELS = [
        'admin' => 'Admin',
        'supervisor' => 'Supervisor',
        'teller' => 'Teller',
    ];

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
            'email' => $request->email,
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

        $data = $request->only(['full_name', 'username', 'email', 'role', 'status']);

        $violation = $this->minimumRoleViolation(
            $user,
            $data['role'] ?? $user->role,
            ($data['status'] ?? $user->status) === 'active',
        );

        if ($violation !== null) {
            return $this->reject("Perubahan akun ditolak karena {$violation}.");
        }

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

        if ($user->id === auth('web')->id()) {
            return $this->reject('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($violation = $this->minimumRoleViolation($user, null, false)) {
            return $this->reject("Penghapusan akun ditolak karena {$violation}.");
        }

        if ($this->hasRelatedRecords($user)) {
            return $this->reject('Akun pengguna tidak dapat dihapus karena masih memiliki data terkait (nasabah/transaksi/laporan).');
        }

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

        if ($user->status === 'active') {
            if ($violation = $this->minimumRoleViolation($user, $user->role, false)) {
                return $this->reject("Pemblokiran akun ditolak karena {$violation}.");
            }
        }

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

    private function hasRelatedRecords(User $user): bool
    {
        return $user->customers()->exists()
            || $user->transactions()->exists()
            || $user->dailyReports()->exists()
            || $user->approvedReports()->exists()
            || CashTransfer::where('supervisor_id', $user->id)->exists();
    }

    private function minimumRoleViolation(User $user, ?string $newRole, bool $newIsActive): ?string
    {
        foreach (self::ROLES as $role) {
            $currentlyCounts = $user->status === 'active' && $user->role === $role;
            $stillCounts = $newRole === $role && $newIsActive;

            if (! $currentlyCounts || $stillCounts) {
                continue;
            }

            $hasOtherActive = User::query()
                ->where('role', $role)
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $hasOtherActive) {
                return sprintf('minimal harus tersisa 1 %s aktif', self::ROLE_LABELS[$role]);
            }
        }

        return null;
    }

    private function reject(string $message): JsonResponse|RedirectResponse
    {
        if ($this->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('error', $message);
    }
}
