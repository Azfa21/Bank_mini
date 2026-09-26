<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Member;
use App\Models\Saver;
use Illuminate\Http\Request;

class MemberSearchController extends Controller
{
    public function search()
    {
        $this->authorize('viewAny', Account::class);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pencarian anggota dan penabung Teller.',
            ]);
        }

        return view('teller.search');
    }

    public function lookup(Request $request)
    {
        $this->authorize('viewAny', Account::class);

        $q = trim($request->input('q', ''));

        if ($q === '') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masukkan nomor rekening, NIS, nama, atau nilai QR Code terlebih dahulu.',
                ], 422);
            }

            return back()->with('error', 'Masukkan nomor rekening, NIS, nama, atau nilai QR Code terlebih dahulu.');
        }

        $account = Account::with('saver')->where('account_number', $q)->first();

        if ($account) {
            return $this->redirectToAccount($request, $account);
        }

        $saver = Saver::with('account')->where('qr_code_value', $q)->first();

        if ($saver && $saver->account) {
            return $this->redirectToAccount($request, $saver->account);
        }

        $member = Member::with('saver.account')->where('nis', $q)->first();

        if ($member) {
            if ($member->saver?->account) {
                return $this->redirectToAccount($request, $member->saver->account);
            }

            return $this->redirectToActivation($request, $member);
        }

        $accounts = Account::with('saver')
            ->where('account_number', 'like', "%{$q}%")
            ->orWhereHas('saver', function ($query) use ($q) {
                $query->where('student_number', 'like', "%{$q}%")
                    ->orWhere('full_name', 'like', "%{$q}%")
                    ->orWhere('class_name', 'like', "%{$q}%");
            })
            ->orderBy('account_number')
            ->limit(25)
            ->get();

        $members = Member::query()
            ->where('status', Member::STATUS_ACCOUNT_INACTIVE)
            ->where(function ($query) use ($q) {
                $query->where('nis', 'like', "%{$q}%")
                    ->orWhere('full_name', 'like', "%{$q}%");
            })
            ->orderBy('full_name')
            ->limit(25)
            ->get();

        if ($accounts->count() === 1 && $members->isEmpty()) {
            return $this->redirectToAccount($request, $accounts->first());
        }

        if ($members->count() === 1 && $accounts->isEmpty()) {
            return $this->redirectToActivation($request, $members->first());
        }

        if ($accounts->isNotEmpty() || $members->isNotEmpty()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'accounts' => $accounts,
                        'members' => $members,
                    ],
                ]);
            }

            return view('teller.search-results', [
                'q' => $q,
                'results' => $accounts,
                'members' => $members,
            ]);
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Anggota atau penabung tidak ditemukan.',
            ], 404);
        }

        return back()->with('error', 'Anggota atau penabung tidak ditemukan.');
    }

    protected function redirectToAccount(Request $request, Account $account)
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return redirect()->route('teller.members.show', $account);
    }

    protected function redirectToActivation(Request $request, Member $member)
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => $member,
                'requires_activation' => true,
            ]);
        }

        return redirect()->route('teller.members.activate', $member);
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $account->load('saver');

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return view('teller.saver-show', compact('account'));
    }
}
