<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    public function recap(User $teller, string $date): array
    {
        $opening = (int) DailyReport::where('teller_id', $teller->id)
            ->whereDate('report_date', '<', $date)
            ->where('status', 'approved')
            ->orderByDesc('report_date')
            ->value('closing_balance_system') ?? 0;

        $totalDeposit = (int) Transaction::where('teller_id', $teller->id)
            ->whereDate('transaction_at', $date)
            ->where('status', 'success')
            ->where('type', 'deposit')
            ->sum('amount');

        $totalWithdrawal = (int) Transaction::where('teller_id', $teller->id)
            ->whereDate('transaction_at', $date)
            ->where('status', 'success')
            ->where('type', 'withdrawal')
            ->sum('amount');

        $systemEnding = $opening + $totalDeposit - $totalWithdrawal;

        return [
            'opening_balance' => $opening,
            'total_deposit' => $totalDeposit,
            'total_withdrawal' => $totalWithdrawal,
            'closing_balance_system' => $systemEnding,
        ];
    }

    public function createReport(User $teller, string $date, int $physicalCash): DailyReport
    {
        return DB::transaction(function () use ($teller, $date, $physicalCash) {
            $recap = $this->recap($teller, $date);

            $cashDifference = $physicalCash - $recap['closing_balance_system'];

            if ($cashDifference !== 0) {
                throw new TransactionValidationException(
                    'Terdapat selisih kas sebesar Rp '.number_format(abs($cashDifference)).'. Laporan tidak dapat dibuat.'
                );
            }

            $existing = DailyReport::where('teller_id', $teller->id)
                ->whereDate('report_date', $date)
                ->whereIn('status', ['draft', 'submitted', 'approved'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new TransactionValidationException('Laporan penutupan kas untuk tanggal ini sudah dibuat.');
            }

            $report = DailyReport::where('teller_id', $teller->id)
                ->whereDate('report_date', $date)
                ->where('status', 'rejected')
                ->lockForUpdate()
                ->first();

            $reportNumber = $report?->report_number ?? $this->generateReportNumber($date);

            $values = [
                'report_number' => $reportNumber,
                'teller_id' => $teller->id,
                'report_date' => $date,
                'opening_balance' => $recap['opening_balance'],
                'total_deposit' => $recap['total_deposit'],
                'total_withdrawal' => $recap['total_withdrawal'],
                'closing_balance_system' => $recap['closing_balance_system'],
                'closing_balance_physical' => $physicalCash,
                'cash_difference' => $cashDifference,
                'status' => 'submitted',
                'approved_by' => null,
                'approved_at' => null,
                'submitted_at' => now(),
                'approval_notes' => null,
            ];

            if ($report) {
                $report->update($values);
            } else {
                $report = DailyReport::create($values);
            }

            AuditLogService::record('daily_report_created', 'daily_report', $report->id, [
                'report_number' => $report->report_number,
                'report_date' => $date,
                'closing_balance_system' => $recap['closing_balance_system'],
                'closing_balance_physical' => $physicalCash,
            ], $teller->id);

            return $report;
        });
    }

    public function approve(DailyReport $report, User $supervisor, string $notes): DailyReport
    {
        return DB::transaction(function () use ($report, $supervisor, $notes) {
            $locked = DailyReport::query()->lockForUpdate()->findOrFail($report->id);

            if ($locked->teller_id === $supervisor->id) {
                throw new TransactionValidationException('Supervisor tidak dapat menyetujui laporan miliknya sendiri.');
            }

            if ($locked->status !== 'submitted') {
                throw new TransactionValidationException('Hanya laporan berstatus menunggu validasi yang dapat disetujui.');
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $supervisor->id,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            CashTransfer::create([
                'report_id' => $locked->id,
                'supervisor_id' => $supervisor->id,
                'amount' => $locked->closing_balance_physical ?? $locked->closing_balance_system,
                'source_location' => 'Laci Teller',
                'destination_location' => 'Brankas Utama',
                'status' => 'transferred',
                'transferred_at' => now(),
                'notes' => $notes,
                'created_at' => now(),
            ]);

            AuditLogService::record('daily_report_approved', 'daily_report', $locked->id, [
                'report_number' => $locked->report_number,
                'approval_notes' => $notes,
            ], $supervisor->id);

            AuditLogService::record('cash_transferred', 'daily_report', $locked->id, [
                'amount' => $locked->closing_balance_physical ?? $locked->closing_balance_system,
                'source_location' => 'Laci Teller',
                'destination_location' => 'Brankas Utama',
            ], $supervisor->id);

            return $locked;
        });
    }

    public function reject(DailyReport $report, User $supervisor, string $notes): DailyReport
    {
        return DB::transaction(function () use ($report, $supervisor, $notes) {
            $locked = DailyReport::query()->lockForUpdate()->findOrFail($report->id);

            if ($locked->status !== 'submitted') {
                throw new TransactionValidationException('Hanya laporan berstatus menunggu validasi yang dapat ditolak.');
            }

            $locked->update([
                'status' => 'rejected',
                'approved_by' => null,
                'approved_at' => null,
                'approval_notes' => $notes,
            ]);

            AuditLogService::record('daily_report_rejected', 'daily_report', $locked->id, [
                'report_number' => $locked->report_number,
                'approval_notes' => $notes,
            ], $supervisor->id);

            return $locked;
        });
    }

    protected function generateReportNumber(string $date): string
    {
        $prefix = 'RPT-'.str_replace('-', '', $date);
        $count = (int) DailyReport::whereDate('report_date', $date)->count();

        return $prefix.'-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
