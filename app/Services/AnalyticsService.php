<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function __construct(
        protected AccountingService $accounting,
    ) {}

    public function summary(?string $asOfDate = null): array
    {
        $date = $asOfDate ?: now()->toDateString();

        $trialBalance = $this->accounting->trialBalance($date);
        $balances = $trialBalance['balances'];

        $cashTeller = max(0, $balances[AccountingService::ACCOUNT_CODE_CASH_TELLER]['net_debit']);
        $cashVault = max(0, $balances[AccountingService::ACCOUNT_CODE_CASH_VAULT]['net_debit']);
        $savings = max(0, $balances[AccountingService::ACCOUNT_CODE_SAVINGS]['net_credit']);

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

        $totals = DB::table('transactions')
            ->where('status', 'success')
            ->whereIn('type', ['deposit', 'withdrawal'])
            ->whereDate('transaction_at', '>=', $start->toDateString())
            ->selectRaw('DATE(transaction_at) as day, type, SUM(amount) as total')
            ->groupBy('day', 'type')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->day.'|'.$row->type => (int) $row->total]);

        $rows = collect();
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $rows->push([
                'date' => $day,
                'label' => $start->copy()->addDays($i)->format('d/m'),
                'deposit' => $totals[$day.'|deposit'] ?? 0,
                'withdrawal' => $totals[$day.'|withdrawal'] ?? 0,
            ]);
        }

        return $rows;
    }

    public function topDepositors(int $limit = 5): Collection
    {
        $totals = DB::table('transactions')
            ->where('status', 'success')
            ->where('type', 'deposit')
            ->selectRaw('account_id, SUM(amount) as total')
            ->groupBy('account_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $accounts = Account::query()
            ->with('customer')
            ->whereIn('id', $totals->pluck('account_id'))
            ->get()
            ->keyBy('id');

        return $totals->map(fn ($row) => [
            'name' => $accounts->has($row->account_id)
                ? ($accounts[$row->account_id]->customer->full_name ?? '—')
                : '—',
            'account_number' => $accounts->has($row->account_id)
                ? $accounts[$row->account_id]->account_number
                : '—',
            'total' => (int) $row->total,
        ]);
    }

    public function tellerPerformance(int $limit = 5): Collection
    {
        $totals = DB::table('transactions')
            ->where('status', 'success')
            ->selectRaw('teller_id, COUNT(*) as total_transactions, SUM(amount) as total_amount')
            ->groupBy('teller_id')
            ->orderByDesc('total_transactions')
            ->limit($limit)
            ->get();

        $tellers = User::query()
            ->whereIn('id', $totals->pluck('teller_id'))
            ->get()
            ->keyBy('id');

        return $totals->map(fn ($row) => [
            'name' => $tellers->has($row->teller_id)
                ? ($tellers[$row->teller_id]->full_name ?? '—')
                : '—',
            'total_transactions' => (int) $row->total_transactions,
            'total_amount' => (int) $row->total_amount,
        ]);
    }
}
