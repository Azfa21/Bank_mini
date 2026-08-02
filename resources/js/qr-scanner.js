import jsQR from 'jsqr';

const startButton = document.getElementById('qr-start');
const stopButton = document.getElementById('qr-stop');
const scanArea = document.getElementById('qr-scan-area');
const video = document.getElementById('qr-video');
const status = document.getElementById('qr-status');
const input = document.getElementById('q');
const form = document.querySelector('form[action*="lookup"]');

let stream = null;
let animationId = null;
let scanning = true;

function setStatus(message) {
    if (status) {
        status.textContent = message || '';
    }
}

async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Kamera tidak didukung di perangkat/browser ini.');
        return;
    }

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: false,
        });

        video.srcObject = stream;
        await video.play();

        scanArea.classList.remove('hidden');
        startButton.classList.add('hidden');
        scanning = true;
        setStatus('Arahkan kamera ke QR Code nasabah...');

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { willReadFrequently: true });

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
                    setStatus('QR Code terdeteksi!');
                    input.value = code.data;
                    form.submit();
                    return;
                }
            }
            animationId = requestAnimationFrame(tick);
        };

        animationId = requestAnimationFrame(tick);
    } catch (err) {
        setStatus('Gagal mengakses kamera. Periksa izin kamera atau gunakan pencarian manual.');
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
    if (startButton) {
        startButton.classList.remove('hidden');
    }
    setStatus('');
}

if (startButton) {
    startButton.addEventListener('click', startCamera);
}

if (stopButton) {
    stopButton.addEventListener('click', stopCamera);
}

window.addEventListener('beforeunload', stopCamera);
