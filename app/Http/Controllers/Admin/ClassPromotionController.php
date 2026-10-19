<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MoveClassMembersRequest;
use App\Http\Requests\Admin\StoreClassPromotionRequest;
use App\Models\Saver;
use App\Services\AccountService;
use App\Services\ClassPromotionService;
use App\Support\ClassRoom;
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

        $savers = Saver::query()
            ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
            ->when($request->input('class') !== null && $request->input('class') !== '', fn ($query) => $query->where('class_name', $request->input('class')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(50)
            ->withQueryString();

        $data = [
            'groups' => collect($groups),
            'withoutClass' => $withoutClass,
            'academicYear' => $academicYear,
            'lastRun' => $lastRun,
            'alreadyRun' => $lastRun === $academicYear,
            'finalYearTotal' => $this->promotions->finalYearTotal($groups),
            'levels' => ClassRoom::LEVELS,
            'majors' => ClassRoom::optionMajors(),
            'classes' => collect($groups)->pluck('class')->reject(static fn (string $class): bool => $class === '')->values(),
            'savers' => $savers,
            // Sasaran yang paling mungkin: tingkat dan jurusan kelas yang sedang difilter.
            'moveDefaults' => ClassRoom::parse((string) $request->input('class')),
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

        $message = $result['savers'] === 0
            ? 'Belum ada penabung yang dipindahkan. Isi dulu kelas tujuan pada baris yang ingin diproses.'
            : "Kenaikan kelas selesai: {$result['savers']} penabung di {$result['classes']} kelas.";

        if ($result['skipped'] > 0) {
            $message .= ' '.$result['skipped'].' baris kelas dilewati karena kelas tujuannya belum lengkap atau sama dengan kelas asal.';
        }

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $result]);
        }

        return redirect()->route('admin.class-promotions.index')->with('success', $message);
    }

    public function move(MoveClassMembersRequest $request)
    {
        $target = ClassRoom::compose(
            (string) $request->validated('level'),
            (string) $request->validated('major'),
            $request->validated('rombel'),
        );

        $result = $this->promotions->move(
            $request->validated('saver_ids'),
            $target,
            $request->user(),
        );

        $message = $result['moved'] === 0
            ? 'Tidak ada yang perlu dipindahkan: penabung terpilih sudah berada di '.$target.'.'
            : $result['moved'].' penabung dipindahkan ke '.$target.'.';

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $result]);
        }

        // Daftar langsung difilter ke kelas tujuan supaya hasil pemindahan terlihat.
        return redirect()
            ->route('admin.class-promotions.index', ['class' => $target])
            ->with('success', $message);
    }
}
