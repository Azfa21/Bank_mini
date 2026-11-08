<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold text-brand-900">DATA SISWA</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Kenaikan &amp; Kelulusan Siswa</h1>
        <p class="text-sm text-stone-600 mt-1">
            {{ $activeSection === 'graduations'
                ? 'Proses kelulusan per angkatan dan putuskan penutupan rekening.'
                : 'Naikkan kelas siswa atau tentukan siapa yang tinggal kelas.' }}
        </p>
    </div>

    <form method="GET" action="{{ route('admin.student-lifecycle.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
        <div class="sm:w-52">
            <x-input-label for="student-lifecycle-section">Pilih proses</x-input-label>
            <x-select id="student-lifecycle-section" name="section">
                <option value="promotions" @selected($activeSection === 'promotions')>Kenaikan kelas</option>
                <option value="graduations" @selected($activeSection === 'graduations')>Kelulusan siswa</option>
            </x-select>
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
    </form>
</div>
