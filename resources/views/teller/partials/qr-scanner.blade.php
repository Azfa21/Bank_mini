<details id="qr-panel" class="mt-4 border-t border-stone-100 pt-4">
    <summary class="min-h-[48px] cursor-pointer py-3 text-[15px] font-semibold text-brand-900 underline decoration-brand-300 underline-offset-4 hover:text-brand-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700">
        Pindai QR Code memakai kamera
    </summary>
    <div class="mt-3">
        <form action="{{ route('teller.members.lookup') }}" method="GET" hidden>
            <input type="hidden" name="q" value="">
        </form>
        <div id="qr-scan-area" class="hidden">
            <video id="qr-video" width="640" height="360" class="w-full aspect-video object-cover rounded-xl border border-stone-200 bg-stone-900" playsinline muted aria-describedby="qr-status" aria-label="Pratinjau kamera pemindai QR"></video>
            <button type="button" id="qr-stop" class="mt-3 inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-stone-700 border border-stone-300 rounded-xl cursor-pointer hover:bg-stone-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700">Hentikan kamera</button>
        </div>

        <p id="qr-status" role="status" aria-live="polite" class="text-[13px] text-stone-600 mt-2.5"></p>
        <p id="qr-fallback" class="text-[13px] text-stone-600 mt-2.5">Kalau kamera tidak tersedia, cari penabung memakai nomor rekening, NIS, atau nama.</p>
    </div>
</details>
