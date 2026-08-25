@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg']) }}>
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
