@if ($errors->any())
    <div role="alert" aria-label="Daftar kesalahan isian" {{ $attributes->merge(['class' => 'mb-4 p-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl']) }}>
        <p class="font-semibold">Periksa isian berikut sebelum lanjut:</p>
        <ul class="list-disc list-inside mt-1.5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
