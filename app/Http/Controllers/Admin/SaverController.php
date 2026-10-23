<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSaverRequest;
use App\Models\Saver;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaverController extends Controller
{
    public function index()
    {
        $savers = Saver::with(['account', 'member'])
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
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $savers,
            ]);
        }

        return view('admin.savers.index', compact('savers'));
    }

    public function edit(Saver $saver): View|RedirectResponse
    {
        if ($saver->isGraduated()) {
            return $this->redirectBecauseGraduated($saver);
        }

        return view('admin.savers.edit', ['saver' => $saver->load('account')]);
    }

    public function update(UpdateSaverRequest $request, Saver $saver)
    {
        if ($saver->isGraduated()) {
            return $this->rejectBecauseGraduated($request, $saver);
        }

        $data = $request->only(['student_number', 'full_name', 'class_name', 'phone_number', 'email', 'status']);

        if ($request->filled('pin')) {
            $data['pin_hash'] = $request->pin;
        }

        $resetPin = $request->filled('pin');
        $saver->update($data);

        if ($resetPin) {
            AuditLogService::record('pin_reset_by_admin', 'saver', $saver->id, [
                'actor_id' => $request->user()->id,
                'actor_name' => $request->user()->full_name,
                'reason' => $request->validated('reset_reason'),
            ], $request->user()->id);
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Profil penabung berhasil diperbarui.',
                'data' => $saver->fresh('account'),
            ]);
        }

        return redirect()->route('admin.savers.index')->with('success', 'Profil penabung berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, Saver $saver)
    {
        if ($saver->isGraduated()) {
            return $this->rejectBecauseGraduated($request, $saver);
        }

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

    // Alumni sudah diarsipkan: mengubah profilnya memutus riwayat yang dipakai
    // laporan dan audit, jadi formulir dikunci untuk semua jalur, bukan hanya tautannya.
    private function rejectBecauseGraduated(Request $request, Saver $saver)
    {
        $message = $this->graduatedMessage($saver);

        if ($this->wantsJson($request)) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return $this->redirectBecauseGraduated($saver);
    }

    private function redirectBecauseGraduated(Saver $saver): RedirectResponse
    {
        return redirect()
            ->route('admin.savers.index')
            ->with('error', $this->graduatedMessage($saver));
    }

    private function graduatedMessage(Saver $saver): string
    {
        return "Data {$saver->full_name} tidak dapat diubah karena sudah menjadi alumni.";
    }
}
