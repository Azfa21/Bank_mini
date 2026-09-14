<?php

return [

    'admin' => [
        ['label' => 'Kelola Pengguna', 'route' => 'admin.users.index', 'routes' => ['admin.users.*']],
        ['label' => 'Kelola Nasabah', 'route' => 'admin.customers.index', 'routes' => ['admin.customers.*', 'admin.accounts.*']],
        ['label' => 'Buku Besar', 'route' => 'admin.reports.general-ledger', 'routes' => ['admin.reports.general-ledger']],
        ['label' => 'Neraca Saldo', 'route' => 'admin.reports.trial-balance', 'routes' => ['admin.reports.trial-balance']],
        ['label' => 'Laba Rugi', 'route' => 'admin.reports.income-statement', 'routes' => ['admin.reports.income-statement']],
        ['label' => 'Neraca', 'route' => 'admin.reports.balance-sheet', 'routes' => ['admin.reports.balance-sheet']],
        ['label' => 'Konfigurasi', 'route' => 'admin.config.edit', 'routes' => ['admin.config.*']],
        ['label' => 'Audit Jurnal', 'route' => 'admin.journals.index', 'routes' => ['admin.journals.*']],
    ],

    'teller' => [
        ['label' => 'Pencarian Nasabah', 'route' => 'teller.customers.search', 'routes' => ['teller.customers.search', 'teller.customers.lookup', 'teller.customers.show', 'teller.deposits.*', 'teller.withdrawals.*', 'teller.transfers.*', 'teller.passbook.*', 'teller.transactions.*']],
        ['label' => 'Registrasi Nasabah', 'route' => 'teller.customers.register', 'routes' => ['teller.customers.register', 'teller.customers.register.store']],
        ['label' => 'Penutupan Kas', 'route' => 'teller.closing.index', 'routes' => ['teller.closing.*']],
    ],

    'supervisor' => [
        ['label' => 'Laporan Harian', 'route' => 'supervisor.reports.index', 'routes' => ['supervisor.reports.show'], 'paths' => ['supervisor/reports']],
        ['label' => 'Buku Besar', 'route' => 'supervisor.reports.general-ledger', 'routes' => ['supervisor.reports.general-ledger']],
        ['label' => 'Neraca Saldo', 'route' => 'supervisor.reports.trial-balance', 'routes' => ['supervisor.reports.trial-balance']],
        ['label' => 'Laba Rugi', 'route' => 'supervisor.reports.income-statement', 'routes' => ['supervisor.reports.income-statement']],
        ['label' => 'Neraca', 'route' => 'supervisor.reports.balance-sheet', 'routes' => ['supervisor.reports.balance-sheet']],
        ['label' => 'Audit Jurnal', 'route' => 'supervisor.journals.index', 'routes' => ['supervisor.journals.*']],
    ],

    'nasabah' => [
        ['label' => 'Riwayat Mutasi', 'route' => 'nasabah.mutasi', 'routes' => ['nasabah.mutasi']],
        ['label' => 'Rekening Koran', 'route' => 'nasabah.statement', 'routes' => ['nasabah.statement']],
    ],

];
