<?php

return [

    /*
     * Alasan: satu tautan Data memuat pengelolaan siswa serta proses kenaikan dan kelulusan.
     * Laporan mengelompokkan keluaran angka dan keputusan kas; penutupan rekening diputuskan
     * dari alur kelulusan. Buku Besar, Neraca Saldo, dan Audit Jurnal ada di satu halaman.
     * Konfigurasi dibiarkan tautan langsung karena hanya satu tujuan; dropdown berisi satu
     * item menambah satu klik tanpa membantu.
     */
    'admin' => [
        [
            'label' => 'Data',
            'items' => [
                ['label' => 'Kelola Pengguna', 'route' => 'admin.users.index', 'routes' => ['admin.users.*']],
                ['label' => 'Data Anggota & Penabung', 'route' => 'admin.member-data.index', 'routes' => ['admin.member-data.*', 'admin.members.*', 'admin.savers.*', 'admin.accounts.*']],
                ['label' => 'Kenaikan & Kelulusan Siswa', 'route' => 'admin.student-lifecycle.index', 'routes' => ['admin.student-lifecycle.*', 'admin.graduations.*', 'admin.class-promotions.*']],
            ],
        ],
        [
            'label' => 'Laporan',
            'items' => [
                ['label' => 'Laporan', 'route' => 'admin.reports.index', 'routes' => ['admin.reports.index', 'admin.reports.monthly*', 'admin.daily-reports.*']],
                ['label' => 'Permintaan Kas', 'route' => 'admin.cash-requests.index', 'routes' => ['admin.cash-requests.*']],
                ['label' => 'Buku Besar & Mutasi Kas', 'route' => 'admin.reports.general-ledger', 'routes' => ['admin.reports.general-ledger', 'admin.reports.trial-balance', 'admin.journals.*']],
            ],
        ],
        ['label' => 'Konfigurasi', 'route' => 'admin.config.edit', 'routes' => ['admin.config.*']],
    ],

    'teller' => [
        ['label' => 'Pencarian Penabung', 'route' => 'teller.members.search', 'routes' => ['teller.members.search', 'teller.members.lookup', 'teller.members.show', 'teller.members.activate', 'teller.members.activate.store', 'teller.deposits.*', 'teller.withdrawals.*', 'teller.transfers.*', 'teller.passbook.*', 'teller.transactions.*']],
        ['label' => 'Booking Penarikan', 'route' => 'teller.withdrawal-bookings.index', 'routes' => ['teller.withdrawal-bookings.*']],
        ['label' => 'Registrasi Anggota', 'route' => 'teller.members.register', 'routes' => ['teller.members.register', 'teller.members.register.store']],
        ['label' => 'Permintaan Kas', 'route' => 'teller.cash-requests.index', 'routes' => ['teller.cash-requests.*']],
        ['label' => 'Penutupan Kas', 'route' => 'teller.closing.index', 'routes' => ['teller.closing.*']],
    ],

];
