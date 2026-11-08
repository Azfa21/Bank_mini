<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-stone-900">Data Anggota &amp; Penabung</h1>
        <p class="mt-1 text-sm text-stone-600">Pilih jenis data untuk melihat dan mengelolanya.</p>
    </div>

    <form method="GET" action="{{ route('admin.member-data.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
        <div class="sm:w-48">
            <x-input-label for="member-data-type">Jenis data</x-input-label>
            <x-select id="member-data-type" name="type">
                <option value="members" @selected($dataType === 'members')>Anggota</option>
                <option value="savers" @selected($dataType === 'savers')>Penabung</option>
            </x-select>
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
    </form>
</div>
