@props(['selected' => [], 'name' => 'denominations'])

@php
    use App\Support\CashDenomination;
    $valueOf = fn (string $key) => (int) ($selected[$key] ?? 0);
@endphp

<div class="space-y-4" data-denomination-root>
    @foreach (CashDenomination::GROUPS as $group => $info)
        <fieldset>
            <legend class="text-[13px] font-semibold text-stone-700">{{ $info['label'] }}</legend>
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                @foreach ($info['values'] as $value)
                    @php $key = $group.'_'.$value; @endphp
                    <label class="flex items-center gap-3 border border-stone-200 rounded-xl px-3 py-2 min-h-[52px] focus-within:border-brand-800 focus-within:ring-2 focus-within:ring-brand-800/20 has-[:focus-visible]:border-brand-800">
                        <span class="text-[13px] font-medium text-stone-700 w-24 shrink-0">{{ CashDenomination::label($value) }}</span>
                        <input type="number" min="0" step="1" inputmode="numeric"
                            name="{{ $name }}[{{ $key }}]"
                            value="{{ $valueOf($key) }}"
                            aria-label="Jumlah {{ CashDenomination::label($value) }}"
                            data-denomination-input
                            data-value="{{ $value }}"
                            class="w-full min-h-[44px] border-0 p-0 text-[15px] tnum text-stone-900 focus:ring-0" />
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
</div>
