import jsQR from 'jsqr';

const panel = document.getElementById('qr-panel');
const scanArea = document.getElementById('qr-scan-area');
const video = document.getElementById('qr-video');
const stopButton = document.getElementById('qr-stop');
const status = document.getElementById('qr-status');
const fallbackHint = document.getElementById('qr-fallback');
const form = panel?.querySelector('form[action*="lookup"]');
const input = form?.querySelector('input[name="q"]');

let stream = null;
let animationId = null;
let scanning = false;

function setStatus(message) {
    if (status) {
        status.textContent = message || '';
    }
}

function showFallbackHint(visible) {
    if (fallbackHint) {
        fallbackHint.classList.toggle('hidden', !visible);
    }
}

function scanQRCode(canvas, context) {
    const tick = () => {
        if (!scanning) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert',
            });

            if (code && code.data) {
                scanning = false;
                input.value = code.data;
                stopCamera();
                setStatus('QR Code terdeteksi. Membuka data penabung...');
                form.submit();
                return;
            }
        }

        animationId = requestAnimationFrame(tick);
    };

    animationId = requestAnimationFrame(tick);
}

async function startCamera() {
    if (stream) return;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Kamera tidak didukung di perangkat atau browser ini.');
        return;
    }

    setStatus('Meminta izin kamera...');

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: false,
        });

        // Panel ditutup saat izin kamera masih diproses: lepaskan kamera.
        if (panel && !panel.open) {
            stopCamera();
            return;
        }

        video.srcObject = stream;
        await video.play();

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { willReadFrequently: true });
        if (!context) {
            stopCamera();
            setStatus('Pemindai QR tidak tersedia. Silakan pakai pencarian manual.');
            return;
        }

        scanning = true;
        scanArea.classList.remove('hidden');
        showFallbackHint(false);
        setStatus('Arahkan kamera ke QR Code nasabah...');

        scanQRCode(canvas, context);
    } catch (err) {
        stopCamera();
        setStatus('Gagal mengakses kamera. Periksa izin kamera, atau pakai pencarian manual di atas.');
    }
}

function stopCamera() {
    scanning = false;
    if (animationId) {
        cancelAnimationFrame(animationId);
        animationId = null;
    }
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }
    if (video) {
        video.srcObject = null;
    }
    if (scanArea) {
        scanArea.classList.add('hidden');
    }
    showFallbackHint(true);
    setStatus('');
}

// Membuka panel pindai langsung menyalakan kamera, menutupnya mematikan kamera.
if (panel) {
    panel.addEventListener('toggle', () => {
        if (panel.open) {
            startCamera();
        } else {
            stopCamera();
        }
    });
}

if (stopButton) {
    // Menutup panel sekaligus jadi satu-satunya cara menghentikan kamera,
    // sehingga membuka panel lagi selalu memulai sesi baru.
    stopButton.addEventListener('click', () => {
        if (panel) {
            panel.open = false;
        } else {
            stopCamera();
        }
    });
}

window.addEventListener('beforeunload', stopCamera);
