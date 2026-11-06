<?php

return [

    /*
     * Alasan: admin punya 11 tujuan. Dikelompokkan supaya bilah tab tetap satu baris tanpa
     * geser ke kanan. "Data" = pengelolaan orang dan rekening, "Laporan" = keluaran angka
     * dan keputusan kas. Penutupan rekening diputuskan di halaman Kelulusan, jadi tidak
     * punya menu sendiri.
     * Neraca Saldo dan Buku Besar kini satu halaman, jadi Laporan cukup satu item untuknya.
     * Konfigurasi dibiarkan tautan langsung karena hanya satu tujuan; dropdown berisi satu
     * item menambah satu klik tanpa membantu.
     */
    'admin' => [
        [
            'label' => 'Data',
            'items' => [
                ['label' => 'Kelola Pengguna', 'route' => 'admin.users.index', 'routes' => ['admin.users.*']],
                ['label' => 'Kelola Anggota', 'route' => 'admin.members.index', 'routes' => ['admin.members.*']],
                ['label' => 'Kelola Penabung', 'route' => 'admin.savers.index', 'routes' => ['admin.savers.*', 'admin.accounts.*']],
                ['label' => 'Kelulusan', 'route' => 'admin.graduations.index', 'routes' => ['admin.graduations.*']],
                ['label' => 'Kenaikan Kelas', 'route' => 'admin.class-promotions.index', 'routes' => ['admin.class-promotions.*']],
            ],
        ],
        [
            'label' => 'Laporan',
            'items' => [
                ['label' => 'Laporan', 'route' => 'admin.reports.index', 'routes' => ['admin.reports.index', 'admin.reports.monthly*', 'admin.daily-reports.*']],
                ['label' => 'Permintaan Kas', 'route' => 'admin.cash-requests.index', 'routes' => ['admin.cash-requests.*']],
                ['label' => 'Buku Besar & Neraca Saldo', 'route' => 'admin.reports.general-ledger', 'routes' => ['admin.reports.general-ledger', 'admin.reports.trial-balance']],
                ['label' => 'Audit Jurnal', 'route' => 'admin.journals.index', 'routes' => ['admin.journals.*']],
            ],
        ],
        ['label' => 'Konfigurasi', 'route' => 'admin.config.edit', 'routes' => ['admin.config.*']],
    ],

    'teller' => [
        ['label' => 'Pencarian Penabung', 'route' => 'teller.members.search', 'routes' => ['teller.members.search', 'teller.members.lookup', 'teller.members.show', 'teller.members.activate', 'teller.members.activate.store', 'teller.deposits.*', 'teller.withdrawals.*', 'teller.transfers.*', 'teller.passbook.*', 'teller.transactions.*']],
        ['label' => 'Registrasi Anggota', 'route' => 'teller.members.register', 'routes' => ['teller.members.register', 'teller.members.register.store']],
        ['label' => 'Permintaan Kas', 'route' => 'teller.cash-requests.index', 'routes' => ['teller.cash-requests.*']],
        ['label' => 'Penutupan Kas', 'route' => 'teller.closing.index', 'routes' => ['teller.closing.*']],
    ],

];
