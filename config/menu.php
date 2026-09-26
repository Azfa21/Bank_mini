<?php

return [

    'admin' => [
        ['label' => 'Kelola Pengguna', 'route' => 'admin.users.index', 'routes' => ['admin.users.*']],
        ['label' => 'Kelola Anggota', 'route' => 'admin.members.index', 'routes' => ['admin.members.*']],
        ['label' => 'Kelola Penabung', 'route' => 'admin.savers.index', 'routes' => ['admin.savers.*', 'admin.accounts.*']],
        ['label' => 'Laporan Bulanan', 'route' => 'admin.reports.monthly', 'routes' => ['admin.reports.monthly']],
        ['label' => 'Buku Besar', 'route' => 'admin.reports.general-ledger', 'routes' => ['admin.reports.general-ledger']],
        ['label' => 'Neraca Saldo', 'route' => 'admin.reports.trial-balance', 'routes' => ['admin.reports.trial-balance']],
        ['label' => 'Laba Rugi', 'route' => 'admin.reports.income-statement', 'routes' => ['admin.reports.income-statement']],
        ['label' => 'Konfigurasi', 'route' => 'admin.config.edit', 'routes' => ['admin.config.*']],
        ['label' => 'Audit Jurnal', 'route' => 'admin.journals.index', 'routes' => ['admin.journals.*']],
    ],

    'teller' => [
        ['label' => 'Pencarian Anggota', 'route' => 'teller.members.search', 'routes' => ['teller.members.search', 'teller.members.lookup', 'teller.members.show', 'teller.members.activate', 'teller.members.activate.store', 'teller.deposits.*', 'teller.withdrawals.*', 'teller.transfers.*', 'teller.passbook.*', 'teller.transactions.*']],
        ['label' => 'Registrasi Anggota', 'route' => 'teller.members.register', 'routes' => ['teller.members.register', 'teller.members.register.store']],
        ['label' => 'Penutupan Kas', 'route' => 'teller.closing.index', 'routes' => ['teller.closing.*']],
    ],

    'supervisor' => [
        ['label' => 'Laporan Harian', 'route' => 'supervisor.reports.index', 'routes' => ['supervisor.reports.show'], 'paths' => ['supervisor/reports']],
        ['label' => 'Laporan Bulanan', 'route' => 'supervisor.reports.monthly', 'routes' => ['supervisor.reports.monthly']],
        ['label' => 'Buku Besar', 'route' => 'supervisor.reports.general-ledger', 'routes' => ['supervisor.reports.general-ledger']],
        ['label' => 'Neraca Saldo', 'route' => 'supervisor.reports.trial-balance', 'routes' => ['supervisor.reports.trial-balance']],
        ['label' => 'Laba Rugi', 'route' => 'supervisor.reports.income-statement', 'routes' => ['supervisor.reports.income-statement']],
        ['label' => 'Audit Jurnal', 'route' => 'supervisor.journals.index', 'routes' => ['supervisor.journals.*']],
    ],

    'nasabah' => [
        ['label' => 'Riwayat Mutasi', 'route' => 'nasabah.mutasi', 'routes' => ['nasabah.mutasi']],
        ['label' => 'Rekening Koran', 'route' => 'nasabah.statement', 'routes' => ['nasabah.statement']],
    ],

];
