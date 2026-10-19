<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveClosureRequest;
use App\Http\Requests\Admin\RejectClosureRequest;
use App\Models\AccountClosure;
use App\Services\AccountClosureService;
use App\Services\AuditLogService;

class AccountClosureController extends Controller
{
    public function __construct(
        protected AccountClosureService $closures,
    ) {}

    public function index()
    {
        $this->authorize('viewAny', AccountClosure::class);

        $closures = AccountClosure::query()
            ->with(['saver', 'account', 'requester', 'decider'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($query, $search) {
                $search = trim($search);

                if ($search !== '') {
                    $query->whereHas('saver', function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%");
                    });
                }
            })
            // Menunggu persetujuan tampil lebih dulu supaya tidak menumpuk.
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $closures,
            ]);
        }

        return view('admin.closures.index', compact('closures'));
    }

    public function show(AccountClosure $closure)
    {
        $this->authorize('view', $closure);

        $closure->load(['saver', 'account', 'requester', 'decider', 'payoutTransaction']);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $closure,
            ]);
        }

        return view('admin.closures.show', compact('closure'));
    }

    public function approve(ApproveClosureRequest $request, AccountClosure $closure)
    {
        $this->authorize('approve', $closure);

        try {
            $this->closures->approve($closure, $request->user(), $request->validated('decision_notes'));
        } catch (TransactionValidationException $e) {
            AuditLogService::record('account_closure_approval_rejected', 'account_closure', $closure->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = 'Penutupan rekening disetujui. Penabung ditandai sebagai alumni.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $closure->fresh(['saver', 'account', 'decider']),
            ]);
        }

        return redirect()->route('admin.closures.show', $closure)->with('success', $message);
    }

    public function reject(RejectClosureRequest $request, AccountClosure $closure)
    {
        $this->authorize('reject', $closure);

        try {
            $this->closures->reject($closure, $request->user(), $request->validated('rejection_reason'));
        } catch (TransactionValidationException $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = 'Permintaan penutupan ditolak. Saldo penabung dikembalikan dan rekening tetap aktif.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $closure->fresh(['saver', 'account', 'decider']),
            ]);
        }

        return redirect()->route('admin.closures.show', $closure)->with('success', $message);
    }
}
