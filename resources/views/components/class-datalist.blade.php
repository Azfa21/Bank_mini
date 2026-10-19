@props(['id' => 'kelas-opsi'])

{{-- Alasan: daftar saran kelas membantu penulisan yang seragam (tingkat, jurusan, rombel)
     tanpa mengunci nilai lama seperti "VII A". Isinya kelas yang sudah ada di data, lalu
     kombinasi tingkat x jurusan x nomor rombel yang paling sering dipakai. --}}
<datalist id="{{ $id }}">
    @foreach (\App\Support\ClassRoom::suggestions() as $option)
        <option value="{{ $option }}"></option>
    @endforeach
</datalist>
