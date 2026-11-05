<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tautan Unduhan Aplikasi Nasabah
    |--------------------------------------------------------------------------
    |
    | Halaman awal menampilkan tombol unduh hanya ketika tautan ini terisi atau
    | berkas APK tersedia di public/unduhan/bank-mini-nasabah.apk. Dengan begitu
    | tidak ada tombol mati saat APK belum dipublikasikan.
    |
    */

    'apk_url' => env('BANKMINI_APK_URL'),

];
