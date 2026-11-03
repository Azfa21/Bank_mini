<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\CashRequest;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CashRequestService
{
    public const ACCOUNT_CODE_CASH_TELLER = AccountingService::ACCOUNT_CODE_CASH_TELLER;

    public const ACCOUNT_CODE_CASH_VAULT = AccountingService::ACCOUNT_CODE_CASH_VAULT;

    public function __construct(
        protected SequenceService $sequences,
    ) {}

    /**
     * Teller mengajukan modal kas. Tidak ada pembatasan permintaan aktif:
     * selama uang laci belum memadai, teller boleh mengajukan lagi. Setelah
     * penutupan kas hari ini dikirim, pengajuan ditutup sampai hari berikutnya;
     * laporan yang ditolak membuka pengajuan kembali.
     */
    public function submit(User $teller, int $amount): CashRequest
    {
        return DB::transaction(function () use ($teller, $amount) {
            if ($amount <= 0) {
                throw new TransactionValidationException('Nominal permintaan kas harus lebih dari nol.');
            }

            $this->assertTellerDayOpen(
                $teller->id,
                'Permintaan kas tidak dapat diajukan karena laporan penutupan kas hari ini sudah dibuat.'
            );

            $key = 'REQ-'.str_replace('-', '', now()->toDateString());

            $request = CashRequest::create([
                'teller_id' => $teller->id,
                'amount' => $amount,
                'request_number' => $this->sequences->formatted($key, $key),
                'status' => 'pending',
            ]);

            AuditLogService::record('cash_request_created', 'cash_request', $request->id, [
                'request_number' => $request->request_number,
                'amount' => $amount,
            ], $teller->id);

            return $request;
        });
    }

    public function approve(CashRequest $request, User $approver, ?string $notes): CashRequest
    {
        return DB::transaction(function () use ($request, $approver, $notes) {
            $locked = CashRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($locked->teller_id === $approver->id) {
                throw new TransactionValidationException('Admin tidak dapat menyetujui permintaan kas miliknya sendiri.');
            }

            if ($locked->status !== 'pending') {
                throw new TransactionValidationException('Hanya permintaan berstatus menunggu yang dapat disetujui.');
            }

            // Persetujuan menambah kas laci, jadi tidak boleh masuk setelah teller
            // mengirim penutupan kas hari ini: laporan itu sudah memakai saldo akhir.
            $this->assertTellerDayOpen(
                $locked->teller_id,
                'Permintaan kas tidak dapat disetujui karena laporan penutupan kas teller hari ini sudah dibuat.'
            );

            // Setuju berarti kas langsung dianggap cair: jurnal memindahkan
            // tanggung jawab kas dari pos kas utama ke pos kas laci teller.
            $locked->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'decision_notes' => $notes,
            ]);

            $this->postJournal($locked);

            AuditLogService::record('cash_request_approved', 'cash_request', $locked->id, [
                'request_number' => $locked->request_number,
                'amount' => $locked->amount,
                'decision_notes' => $notes,
            ], $approver->id);

            return $locked;
        });
    }

    public function reject(CashRequest $request, User $approver, string $notes): CashRequest
    {
        return DB::transaction(function () use ($request, $approver, $notes) {
            $locked = CashRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($locked->status !== 'pending') {
                throw new TransactionValidationException('Hanya permintaan berstatus menunggu yang dapat ditolak.');
            }

            $locked->update([
                'status' => 'rejected',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'decision_notes' => $notes,
            ]);

            AuditLogService::record('cash_request_rejected', 'cash_request', $locked->id, [
                'request_number' => $locked->request_number,
                'amount' => $locked->amount,
                'decision_notes' => $notes,
            ], $approver->id);

            return $locked;
        });
    }

    /**
     * Penutupan kas mengunci laci untuk tanggal tersebut. Setelah laporan
     * disubmitkan, kas baru tidak boleh masuk lagi karena saldo akhir laporan
     * sudah dihitung dan diserahkan ke Administrator.
     */
    protected function assertTellerDayOpen(int $tellerId, string $blockedMessage): void
    {
        $closedReport = DailyReport::query()
            ->where('teller_id', $tellerId)
            ->whereDate('report_date', now()->toDateString())
            ->whereIn('status', ['submitted', 'approved'])
            ->lockForUpdate()
            ->first();

        if ($closedReport !== null) {
            throw new TransactionValidationException($blockedMessage);
        }
    }

    /**
     * Jurnal pencairan: Debet 101 Kas Teller, Kredit 102 Kas Utama.
     */
    protected function postJournal(CashRequest $request): void
    {
        $description = 'Kas dikirim dari Kas Utama ke Laci Teller ('.$request->request_number.')';

        $journalRows = [
            [
                'account_code' => self::ACCOUNT_CODE_CASH_TELLER,
                'entry_type' => 'debit',
                'debit_amount' => $request->amount,
                'credit_amount' => 0,
                'description' => $description,
            ],
            [
                'account_code' => self::ACCOUNT_CODE_CASH_VAULT,
                'entry_type' => 'credit',
                'debit_amount' => 0,
                'credit_amount' => $request->amount,
                'description' => $description,
            ],
        ];

        $journalPrefix = 'JRN-REQ-'.now()->format('Ymd');
        foreach ($journalRows as $idx => $row) {
            JurnalAkuntansi::create([
                'transaction_id' => null,
                'cash_transfer_id' => null,
                'cash_request_id' => $request->id,
                'journal_number' => $journalPrefix.'-'.str_pad((string) $request->id, 4, '0', STR_PAD_LEFT).'-'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                'account_code' => $row['account_code'],
                'entry_type' => $row['entry_type'],
                'amount' => $request->amount,
                'debit_amount' => $row['debit_amount'],
                'credit_amount' => $row['credit_amount'],
                'description' => $row['description'],
                'status' => 'posted',
                'journal_at' => now(),
            ]);
        }
    }
}
