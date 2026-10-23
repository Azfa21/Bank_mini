<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pemulihan Kredensial Berbasis Email (OTP)
    |--------------------------------------------------------------------------
    |
    | Konfigurasi masa berlaku kode OTP, jumlah percobaan verifikasi, jeda
    | pengiriman ulang, serta batas pengiriman kode per jam untuk mencegah
    | penyalahgunaan alur lupa password.
    |
    */

    'otp_length' => (int) env('RECOVERY_OTP_LENGTH', 6),

    'otp_expire_minutes' => (int) env('RECOVERY_OTP_EXPIRE_MINUTES', 10),

    'otp_max_attempts' => (int) env('RECOVERY_OTP_MAX_ATTEMPTS', 5),

    'otp_resend_cooldown_seconds' => (int) env('RECOVERY_OTP_RESEND_COOLDOWN', 60),

    'otp_max_per_hour' => (int) env('RECOVERY_OTP_MAX_PER_HOUR', 5),

    'transaction_attempts' => (int) env('RECOVERY_TRANSACTION_ATTEMPTS', 3),

];
