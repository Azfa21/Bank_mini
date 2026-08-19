<?php

namespace App\Http\Controllers;

use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QrCodeController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodes,
    ) {}

    public function show(Request $request): Response
    {
        $data = (string) $request->input('data', '');

        if ($data === '') {
            abort(422, 'Data QR Code tidak boleh kosong.');
        }

        $svg = $this->qrCodes->svg($data, (int) $request->input('size', 220));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function download(Request $request): Response
    {
        $data = (string) $request->input('data', '');

        if ($data === '') {
            abort(422, 'Data QR Code tidak boleh kosong.');
        }

        $filename = 'qr-'.str_replace(['/', '\\', ' '], '-', $data).'.svg';

        $svg = $this->qrCodes->svg($data, (int) $request->input('size', 300));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
