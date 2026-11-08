<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassPromotionRequest;
use App\Services\AccountService;
use App\Services\ClassPromotionService;
use Illuminate\Http\Request;

class ClassPromotionController extends Controller
{
    public function __construct(
        protected ClassPromotionService $promotions,
    ) {}

    public function index(Request $request)
    {
        $groups = $this->promotions->classGroups();
        $withoutClass = $this->promotions->withoutClass();
        $academicYear = AccountService::academicYear();
        $lastRun = $this->promotions->lastRun();
        $ready = collect($groups)->where('kind', 'ready');

        $data = [
            'groups' => collect($groups),
            'ready' => $ready,
            'students' => $this->promotions->studentsByClass($groups),
            'withoutClass' => $withoutClass,
            'academicYear' => $academicYear,
            'lastRun' => $lastRun,
            'alreadyRun' => $lastRun === $academicYear,
            'finalYear' => $this->promotions->finalYearCounts($groups),
            'readyMembers' => (int) $ready->sum('members'),
            'readySavers' => (int) $ready->sum('savers'),
        ];

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('admin.promotions.index', $data);
    }

    public function store(StoreClassPromotionRequest $request)
    {
        $academicYear = (string) $request->validated('academic_year');

        // Alasan: siswa baru kelas X yang diaktivasi setelah kenaikan kelas akan ikut naik
        // lagi kalau tombol ini ditekan dua kali pada tahun ajaran yang sama.
        if ($this->promotions->lastRun() === $academicYear && ! $request->boolean('confirm_repeat')) {
            $message = 'Kenaikan kelas untuk tahun ajaran '.$academicYear.' sudah pernah dijalankan. Centang konfirmasi kalau memang ingin menjalankannya lagi.';

            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withInput()->with('error', $message);
        }

        $result = $this->promotions->apply(
            $request->validated('rows') ?? [],
            $academicYear,
            $request->user(),
        );

        $message = $this->resultMessage($result);

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $result]);
        }

        return redirect()->route('admin.student-lifecycle.index', ['section' => 'promotions'])->with('success', $message);
    }

    /**
     * @param  array{classes: int, members: int, savers: int, retained: int, retained_students: int, skipped: int, mapping: array<string, string>}  $result
     */
    private function resultMessage(array $result): string
    {
        $moved = [];

        if ($result['members'] > 0) {
            $moved[] = $result['members'].' anggota';
        }

        if ($result['savers'] > 0) {
            $moved[] = $result['savers'].' penabung';
        }

        $message = $moved === []
            ? 'Belum ada yang dinaikkan.'
            : 'Kenaikan kelas selesai: '.implode(' dan ', $moved).' di '.$result['classes'].' kelas.';

        if ($moved === [] && $result['retained_students'] === 0) {
            $message .= ' Pilih Naik ke tingkat berikutnya pada minimal satu kelas.';
        }

        if ($result['retained'] > 0) {
            $message .= ' '.$result['retained'].' kelas ditandai tinggal kelas.';
        }

        if ($result['retained_students'] > 0) {
            $message .= ' '.$result['retained_students'].' siswa ditandai tinggal kelas.';
        }

        if ($result['skipped'] > 0) {
            $message .= ' '.$result['skipped'].' baris kelas dilewati karena tingkatnya tidak dikenali atau datanya sudah tidak ada.';
        }

        return $message;
    }
}
