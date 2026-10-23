<?php

namespace App\Http\Controllers\Nasabah;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nasabah\ChangeTransactionPinRequest;
use App\Services\SaverCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PinController extends Controller
{
    public function __construct(
        protected SaverCredentialService $credentials,
    ) {}

    public function edit(): View
    {
        return view('nasabah.pin');
    }

    public function update(ChangeTransactionPinRequest $request): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('nasabah')->user();

        try {
            $this->credentials->changeTransactionPin($customer, $request->current_pin, $request->pin);
        } catch (TransactionValidationException $exception) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->with('error', $exception->getMessage())->withInput();
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'PIN transaksi berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'PIN transaksi berhasil diperbarui.');
    }
}
