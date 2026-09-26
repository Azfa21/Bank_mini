<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSaverRequest;
use App\Models\Saver;
use Illuminate\View\View;

class SaverController extends Controller
{
    public function index()
    {
        $savers = Saver::with('account')
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($query, $search) {
                $search = trim($search);

                if ($search !== '') {
                    $query->where(function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%")
                            ->orWhere('class_name', 'like', "%{$search}%");
                    });
                }
            })
            ->latest()
            ->paginate(15);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $savers,
            ]);
        }

        return view('admin.savers.index', compact('savers'));
    }

    public function edit(Saver $saver): View
    {
        return view('admin.savers.edit', ['saver' => $saver->load('account')]);
    }

    public function update(UpdateSaverRequest $request, Saver $saver)
    {
        $data = $request->only(['student_number', 'full_name', 'class_name', 'phone_number', 'email', 'status']);

        if ($request->filled('pin')) {
            $data['pin_hash'] = $request->pin;
        }

        $saver->update($data);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Profil penabung berhasil diperbarui.',
                'data' => $saver->fresh('account'),
            ]);
        }

        return redirect()->route('admin.savers.index')->with('success', 'Profil penabung berhasil diperbarui.');
    }

    public function toggleStatus(Saver $saver)
    {
        $saver->update(['status' => $saver->status === 'active' ? 'blocked' : 'active']);

        $label = $saver->status === 'active' ? 'diaktifkan' : 'diblokir';

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Penabung {$saver->full_name} berhasil {$label}.",
                'data' => $saver->fresh(),
            ]);
        }

        return redirect()->route('admin.savers.index')->with('success', "Penabung {$saver->full_name} berhasil {$label}.");
    }

    public function destroy(Saver $saver)
    {
        $name = $saver->full_name;

        $saver->account?->update(['status' => 'closed', 'closed_at' => now()]);

        $saver->delete();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Penabung {$name} berhasil dihapus. Histori transaksi tetap tersimpan.",
            ]);
        }

        return redirect()->route('admin.savers.index')
            ->with('success', "Penabung {$name} berhasil dihapus. Histori transaksi tetap tersimpan.");
    }
}
