@props(['selected' => [], 'name' => 'denominations'])

@php
    use App\Support\CashDenomination;
    $valueOf = fn (string $key) => (int) ($selected[$key] ?? 0);
@endphp

<div class="space-y-4" data-denomination-root>
    @foreach (CashDenomination::GROUPS as $group => $info)
        <div>
            <p class="text-xs font-medium text-gray-600">{{ $info['label'] }}</p>
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach ($info['values'] as $value)
                    @php $key = $group.'_'.$value; @endphp
                    <label class="flex items-center gap-2 border border-gray-200 rounded-lg px-2 py-1.5">
                        <span class="text-xs text-gray-500 w-20 shrink-0">{{ CashDenomination::label($value) }}</span>
                        <input type="number" min="0" step="1"
                            name="{{ $name }}[{{ $key }}]"
                            value="{{ $valueOf($key) }}"
                            data-denomination-input
                            data-value="{{ $value }}"
                            class="w-full border-0 p-0 text-sm focus:ring-0" />
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
