<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public const ACCOUNT_CODE_CASH_TELLER = '101';

    public const ACCOUNT_CODE_CASH_VAULT = '102';

    public const ACCOUNT_CODE_SAVINGS = '201';

    public function summary(?string $asOfDate = null): array
    {
        $date = $asOfDate ?: now()->toDateString();

        $netBalance = function (string $code, string $normal) use ($date): int {
            $debit = (int) JurnalAkuntansi::where('account_code', $code)
                ->whereDate('journal_at', '<=', $date)
                ->sum('debit_amount');

            $credit = (int) JurnalAkuntansi::where('account_code', $code)
                ->whereDate('journal_at', '<=', $date)
                ->sum('credit_amount');

            return $normal === 'debit' ? ($debit - $credit) : ($credit - $debit);
        };

        $cashTeller = max(0, $netBalance(self::ACCOUNT_CODE_CASH_TELLER, 'debit'));
        $cashVault = max(0, $netBalance(self::ACCOUNT_CODE_CASH_VAULT, 'debit'));
        $savings = max(0, $netBalance(self::ACCOUNT_CODE_SAVINGS, 'credit'));

        $totalCash = $cashTeller + $cashVault;
        $liquidityRatio = $savings > 0 ? round(($totalCash / $savings) * 100, 2) : 100.0;

        return [
            'cash_teller' => $cashTeller,
            'cash_vault' => $cashVault,
            'total_cash' => $totalCash,
            'total_savings' => $savings,
            'liquidity_ratio_percent' => $liquidityRatio,
            'total_accounts' => Account::where('status', 'active')->count(),
            'total_customers' => Customer::where('status', 'active')->count(),
            'total_users' => User::where('status', 'active')->count(),
        ];
    }

    public function trend(int $days = 7): Collection
    {
        $start = now()->startOfDay()->subDays($days - 1);

        $deposits = Transaction::query()
            ->where('status', 'success')
            ->where('type', 'deposit')
            ->whereDate('transaction_at', '>=', $start->toDateString())
            ->get()
            ->groupBy(fn ($t) => $t->transaction_at->toDateString())
            ->map->sum('amount');

        $withdrawals = Transaction::query()
            ->where('status', 'success')
            ->where('type', 'withdrawal')
            ->whereDate('transaction_at', '>=', $start->toDateString())
            ->get()
            ->groupBy(fn ($t) => $t->transaction_at->toDateString())
            ->map->sum('amount');

        $rows = collect();
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $rows->push([
                'date' => $day,
                'label' => $start->copy()->addDays($i)->format('d/m'),
                'deposit' => (int) ($deposits[$day] ?? 0),
                'withdrawal' => (int) ($withdrawals[$day] ?? 0),
            ]);
        }

        return $rows;
    }

    public function topDepositors(int $limit = 5): Collection
    {
        return Transaction::query()
            ->where('status', 'success')
            ->where('type', 'deposit')
            ->selectRaw('account_id, SUM(amount) as total')
            ->groupBy('account_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('account.customer')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->account?->customer?->full_name ?? '—',
                'account_number' => $row->account?->account_number ?? '—',
                'total' => (int) $row->total,
            ]);
    }

    public function tellerPerformance(int $limit = 5): Collection
    {
        return Transaction::query()
            ->where('status', 'success')
            ->selectRaw('teller_id, COUNT(*) as total_transactions, SUM(amount) as total_amount')
            ->groupBy('teller_id')
            ->orderByDesc('total_transactions')
            ->limit($limit)
            ->with('teller')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->teller?->full_name ?? '—',
                'total_transactions' => (int) $row->total_transactions,
                'total_amount' => (int) $row->total_amount,
            ]);
    }
}
