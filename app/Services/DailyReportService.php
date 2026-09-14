<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CashDenomination;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    public function __construct(
        protected SequenceService $sequences,
    ) {}

    public function recap(User $teller, string $date): array
    {
        // Model brankas: setiap laporan yang disetujui menyapu seluruh kas laci ke brankas,
        // sehingga saldo awal = mutasi kas yang belum tersapu sebelum tanggal ini.
        $before = $this->cashFlow($teller, '<', $date);
        $today = $this->cashFlow($teller, '=', $date);

        $sweptBefore = (int) DailyReport::where('teller_id', $teller->id)
            ->whereDate('report_date', '<', $date)
            ->where('status', 'approved')
            ->sum(DB::raw('COALESCE(closing_balance_physical, closing_balance_system)'));

        $opening = $before['deposit'] - $before['withdrawal'] - $sweptBefore;

        $systemEnding = $opening + $today['deposit'] - $today['withdrawal'];

        $totalAdminFee = (int) Transaction::where('teller_id', $teller->id)
            ->whereDate('transaction_at', $date)
            ->whereIn('status', ['success', 'reversed'])
            ->where('type', 'withdrawal')
            ->sum('admin_fee');

        return [
            'opening_balance' => $opening,
            'total_deposit' => $today['deposit'],
            'total_withdrawal' => $today['withdrawal'],
            'total_admin_fee' => $totalAdminFee,
            'closing_balance_system' => $systemEnding,
        ];
    }

    /**
     * Arus kas laci berdasarkan transaksi setoran/penarikan termasuk koreksi
     * (jurnal pembalik) yang memengaruhi uang tunai.
     *
     * @return array{deposit: int, withdrawal: int}
     */
    protected function cashFlow(User $teller, string $operator, string $date): array
    {
        $activeStatuses = ['success', 'reversed'];

        $depositQuery = Transaction::where('teller_id', $teller->id)
            ->where('type', 'deposit')
            ->whereIn('status', $activeStatuses)
            ->whereDate('transaction_at', $operator, $date);

        $withdrawalQuery = Transaction::where('teller_id', $teller->id)
            ->where('type', 'withdrawal')
            ->whereIn('status', $activeStatuses)
            ->whereDate('transaction_at', $operator, $date);

        $deposit = (int) $depositQuery->sum('amount');
        $withdrawal = (int) $withdrawalQuery->sum('amount');

        // Koreksi penarikan mengembalikan uang tunai ke laci (bertambah seperti setoran),
        // koreksi setoran mengeluarkan uang tunai dari laci (berkurang seperti penarikan).
        $deposit += (int) Transaction::where('teller_id', $teller->id)
            ->where('type', 'reversal')
            ->where('status', 'success')
            ->whereHas('reversalOf', fn ($q) => $q->where('type', 'withdrawal'))
            ->whereDate('transaction_at', $operator, $date)
            ->sum('amount');

        $withdrawal += (int) Transaction::where('teller_id', $teller->id)
            ->where('type', 'reversal')
            ->where('status', 'success')
            ->whereHas('reversalOf', fn ($q) => $q->where('type', 'deposit'))
            ->whereDate('transaction_at', $operator, $date)
            ->sum('amount');

        return ['deposit' => $deposit, 'withdrawal' => $withdrawal];
    }

    public function createReport(User $teller, string $date, int $physicalCash, array $breakdown = []): DailyReport
    {
        return DB::transaction(function () use ($teller, $date, $physicalCash, $breakdown) {
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

            $reportNumber = $report !== null
                ? $report->report_number
                : $this->generateReportNumber($date);

            $normalizedBreakdown = CashDenomination::normalize($breakdown);

            $values = [
                'report_number' => $reportNumber,
                'teller_id' => $teller->id,
                'report_date' => $date,
                'opening_balance' => $recap['opening_balance'],
                'total_deposit' => $recap['total_deposit'],
                'total_withdrawal' => $recap['total_withdrawal'],
                'total_admin_fee' => $recap['total_admin_fee'],
                'closing_balance_system' => $recap['closing_balance_system'],
                'closing_balance_physical' => $physicalCash,
                'closing_breakdown' => $normalizedBreakdown === [] ? null : $normalizedBreakdown,
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

    public const ACCOUNT_CODE_CASH_TELLER = AccountingService::ACCOUNT_CODE_CASH_TELLER;

    public const ACCOUNT_CODE_CASH_VAULT = AccountingService::ACCOUNT_CODE_CASH_VAULT;

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

            $transferAmount = $locked->closing_balance_physical ?? $locked->closing_balance_system;

            if ($transferAmount > 0 && $this->cashLedgerHasEntries()) {
                $drawerBalance = $this->cashTellerLedgerBalance();

                if ($transferAmount > $drawerBalance) {
                    throw new TransactionValidationException(
                        'Pemindahan kas ditolak karena saldo kas laci berdasarkan jurnal hanya Rp '.number_format(max(0, $drawerBalance)).'.'
                    );
                }
            }

            $cashTransfer = CashTransfer::create([
                'report_id' => $locked->id,
                'supervisor_id' => $supervisor->id,
                'amount' => $transferAmount,
                'source_location' => 'Laci Teller',
                'destination_location' => 'Brankas Utama',
                'status' => 'transferred',
                'transferred_at' => now(),
                'notes' => $notes,
                'created_at' => now(),
            ]);

            if ($transferAmount > 0) {
                // Post double-entry journal for Cash Transfer: Debet 102 Kas Brankas, Kredit 101 Kas Teller
                $journalRows = [
                    [
                        'account_code' => self::ACCOUNT_CODE_CASH_VAULT,
                        'entry_type' => 'debit',
                        'debit_amount' => $transferAmount,
                        'credit_amount' => 0,
                        'description' => 'Pemindahan kas Laci Teller ke Brankas Utama (Lap: '.$locked->report_number.')',
                    ],
                    [
                        'account_code' => self::ACCOUNT_CODE_CASH_TELLER,
                        'entry_type' => 'credit',
                        'debit_amount' => 0,
                        'credit_amount' => $transferAmount,
                        'description' => 'Pemindahan kas Laci Teller ke Brankas Utama (Lap: '.$locked->report_number.')',
                    ],
                ];

                $journalPrefix = 'JRN-TRF-'.Carbon::parse($locked->report_date)->format('Ymd');
                foreach ($journalRows as $idx => $row) {
                    JurnalAkuntansi::create([
                        'transaction_id' => null,
                        'cash_transfer_id' => $cashTransfer->id,
                        'journal_number' => $journalPrefix.'-'.str_pad((string) $cashTransfer->id, 4, '0', STR_PAD_LEFT).'-'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                        'account_code' => $row['account_code'],
                        'entry_type' => $row['entry_type'],
                        'amount' => $transferAmount,
                        'debit_amount' => $row['debit_amount'],
                        'credit_amount' => $row['credit_amount'],
                        'description' => $row['description'],
                        'status' => 'posted',
                        'journal_at' => now(),
                    ]);
                }
            }

            AuditLogService::record('daily_report_approved', 'daily_report', $locked->id, [
                'report_number' => $locked->report_number,
                'approval_notes' => $notes,
            ], $supervisor->id);

            AuditLogService::record('cash_transferred', 'daily_report', $locked->id, [
                'amount' => $transferAmount,
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
        $key = 'RPT-'.str_replace('-', '', $date);

        return $this->sequences->formatted($key, $key);
    }

    protected function cashLedgerHasEntries(): bool
    {
        return JurnalAkuntansi::where('account_code', AccountingService::ACCOUNT_CODE_CASH_TELLER)->exists();
    }

    protected function cashTellerLedgerBalance(): int
    {
        return (int) JurnalAkuntansi::where('account_code', AccountingService::ACCOUNT_CODE_CASH_TELLER)
            ->selectRaw('COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) as balance')
            ->value('balance');
    }
}
