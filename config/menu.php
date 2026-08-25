<?php

return [

    'admin' => [
        ['label' => 'Kelola Pengguna', 'route' => 'admin.users.index', 'routes' => ['admin.users.*']],
        ['label' => 'Kelola Nasabah', 'route' => 'admin.customers.index', 'routes' => ['admin.customers.*', 'admin.accounts.*']],
        ['label' => 'Laporan Keuangan', 'route' => 'admin.reports.general-ledger', 'routes' => ['admin.reports.*']],
        ['label' => 'Konfigurasi', 'route' => 'admin.config.edit', 'routes' => ['admin.config.*']],
        ['label' => 'Audit Jurnal', 'route' => 'admin.journals.index', 'routes' => ['admin.journals.*']],
    ],

    'teller' => [
        ['label' => 'Pencarian Nasabah', 'route' => 'teller.customers.search', 'routes' => ['teller.customers.*', 'teller.deposits.*', 'teller.withdrawals.*', 'teller.transfers.*', 'teller.passbook.*', 'teller.transactions.*']],
        ['label' => 'Penutupan Kas', 'route' => 'teller.closing.index', 'routes' => ['teller.closing.*']],
    ],

    'supervisor' => [
        ['label' => 'Laporan Harian', 'route' => 'supervisor.reports.index', 'routes' => ['supervisor.reports.show'], 'paths' => ['supervisor/reports']],
        ['label' => 'Laporan Keuangan', 'route' => 'supervisor.reports.general-ledger', 'paths' => ['supervisor/reports-accounting*']],
        ['label' => 'Audit Jurnal', 'route' => 'supervisor.journals.index', 'routes' => ['supervisor.journals.*']],
    ],

    'nasabah' => [
        ['label' => 'Riwayat Mutasi', 'route' => 'nasabah.mutasi', 'routes' => ['nasabah.mutasi']],
        ['label' => 'Rekening Koran', 'route' => 'nasabah.statement', 'routes' => ['nasabah.statement']],
    ],

];
