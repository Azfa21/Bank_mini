<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\CashRequest;
use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    public function __construct(
        protected SequenceService $sequences,
    ) {}

    public function recap(User $teller, string $date): array
    {
        // Model kas utama: setiap laporan yang disetujui menyapu seluruh kas laci ke kas utama,
        // sehingga saldo awal = mutasi kas yang belum tersapu sebelum tanggal ini.
        $before = $this->cashFlow($teller, '<', $date);
        $today = $this->cashFlow($teller, '=', $date);

        // Kas yang cair dari kas utama (permintaan kas teller yang disetujui)
        // menambah isian laci, jadi ikut dihitung pada mutasi dan saldo laporan.
        $receivedBefore = $this->cashReceived($teller, '<', $date);
        $receivedToday = $this->cashReceived($teller, '=', $date);

        $sweptBefore = (int) DailyReport::where('teller_id', $teller->id)
            ->whereDate('report_date', '<', $date)
            ->where('status', 'approved')
            ->sum('closing_balance_system');

        $opening = $before['deposit'] - $before['withdrawal'] + $receivedBefore - $sweptBefore;

        $systemEnding = $opening + $today['deposit'] + $receivedToday - $today['withdrawal'];

        return [
            'opening_balance' => $opening,
            'total_deposit' => $today['deposit'],
            'total_cash_received' => $receivedToday,
            'total_withdrawal' => $today['withdrawal'],
            'closing_balance_system' => $systemEnding,
        ];
    }

    /**
     * Total kas permintaan teller yang sudah disetujui dan dianggap cair.
     */
    protected function cashReceived(User $teller, string $operator, string $date): int
    {
        return (int) CashRequest::where('teller_id', $teller->id)
            ->where('status', 'approved')
            ->whereDate('approved_at', $operator, $date)
            ->sum('amount');
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

    public function createReport(User $teller, string $date): DailyReport
    {
        return DB::transaction(function () use ($teller, $date) {
            $recap = $this->recap($teller, $date);

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

            $values = [
                'report_number' => $reportNumber,
                'teller_id' => $teller->id,
                'report_date' => $date,
                'opening_balance' => $recap['opening_balance'],
                'total_deposit' => $recap['total_deposit'],
                'total_cash_received' => $recap['total_cash_received'],
                'total_withdrawal' => $recap['total_withdrawal'],
                'closing_balance_system' => $recap['closing_balance_system'],
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
            ], $teller->id);

            return $report;
        });
    }

    public const ACCOUNT_CODE_CASH_TELLER = AccountingService::ACCOUNT_CODE_CASH_TELLER;

    public const ACCOUNT_CODE_CASH_VAULT = AccountingService::ACCOUNT_CODE_CASH_VAULT;

    public function approve(DailyReport $report, User $approver, string $notes): DailyReport
    {
        return DB::transaction(function () use ($report, $approver, $notes) {
            $locked = DailyReport::query()->lockForUpdate()->findOrFail($report->id);

            if ($locked->transactions()->exists()) {
                $teller = User::findOrFail($locked->teller_id);
                $currentRecap = $this->recap(
                    $teller,
                    $locked->report_date->toDateString(),
                );

                if ((int) $currentRecap['closing_balance_system'] !== (int) $locked->closing_balance_system) {
                    throw new TransactionValidationException(
                        'Arus kas tanggal tersebut berubah setelah laporan dibuat. Tolak laporan dan minta Teller menghitung ulang sebelum disetujui.'
                    );
                }
            }

            if ($locked->teller_id === $approver->id) {
                throw new TransactionValidationException('Admin tidak dapat menyetujui laporan miliknya sendiri.');
            }

            if ($locked->status !== 'submitted') {
                throw new TransactionValidationException('Hanya laporan berstatus menunggu validasi yang dapat disetujui.');
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            $transferAmount = $locked->closing_balance_system;

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
                'approved_by' => $approver->id,
                'amount' => $transferAmount,
                'source_location' => 'Laci Teller',
                'destination_location' => 'Kas Utama',
                'status' => 'transferred',
                'transferred_at' => now(),
                'notes' => $notes,
                'created_at' => now(),
            ]);

            if ($transferAmount > 0) {
                // Post double-entry journal for Cash Transfer: Debet 102 Kas Utama, Kredit 101 Kas Teller
                $journalRows = [
                    [
                        'account_code' => self::ACCOUNT_CODE_CASH_VAULT,
                        'entry_type' => 'debit',
                        'debit_amount' => $transferAmount,
                        'credit_amount' => 0,
                        'description' => 'Pemindahan kas Laci Teller ke Kas Utama (Lap: '.$locked->report_number.')',
                    ],
                    [
                        'account_code' => self::ACCOUNT_CODE_CASH_TELLER,
                        'entry_type' => 'credit',
                        'debit_amount' => 0,
                        'credit_amount' => $transferAmount,
                        'description' => 'Pemindahan kas Laci Teller ke Kas Utama (Lap: '.$locked->report_number.')',
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
            ], $approver->id);

            AuditLogService::record('cash_transferred', 'daily_report', $locked->id, [
                'amount' => $transferAmount,
                'source_location' => 'Laci Teller',
                'destination_location' => 'Kas Utama',
            ], $approver->id);

            return $locked;
        });
    }

    public function reject(DailyReport $report, User $approver, string $notes): DailyReport
    {
        return DB::transaction(function () use ($report, $approver, $notes) {
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
            ], $approver->id);

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
