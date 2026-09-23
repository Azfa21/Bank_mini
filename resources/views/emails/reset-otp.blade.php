@php
    $label = $purpose === 'pin' ? 'PIN' : 'Password';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi Reset {{ $label }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f4;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border:1px solid #e7e5e4;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#166534;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:16px;font-weight:bold;">Bank Mini Sekolah</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px;font-size:15px;">Halo, {{ $recipientName }}.</p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#44403c;">
                                Kami menerima permintaan reset {{ $label }} untuk akun Anda. Masukkan kode verifikasi berikut pada halaman pemulihan kredensial:
                            </p>
                            <p style="margin:0 0 20px;text-align:center;">
                                <span style="display:inline-block;padding:14px 24px;background-color:#f0fdf4;border:1px dashed #166534;border-radius:12px;font-size:30px;font-weight:bold;letter-spacing:8px;color:#166534;">{{ $code }}</span>
                            </p>
                            <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#57534e;">
                                Kode ini berlaku selama {{ (int) config('recovery.otp_expire_minutes', 10) }} menit dan hanya dapat digunakan satu kali.
                                Jangan bagikan kode ini kepada siapa pun, termasuk petugas bank.
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#57534e;">
                                Jika Anda tidak merasa meminta reset {{ $label }}, abaikan email ini. {{ $label }} Anda tidak akan berubah.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background-color:#fafaf9;border-top:1px solid #e7e5e4;">
                            <p style="margin:0;font-size:12px;color:#78716c;">Email otomatis dari sistem Bank Mini Sekolah. Mohon tidak membalas email ini.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
