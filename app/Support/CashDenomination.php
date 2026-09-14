<?php

namespace App\Support;

class CashDenomination
{
    /**
     * Kelompok pecahan uang kertas dan logam yang berlaku pada kas Bank Mini.
     *
     * @var array<string, array{label: string, values: array<int, int>}>
     */
    public const GROUPS = [
        'kertas' => [
            'label' => 'Uang Kertas',
            'values' => [100000, 50000, 20000, 10000, 5000, 2000, 1000],
        ],
        'logam' => [
            'label' => 'Uang Logam',
            'values' => [1000, 500, 200, 100, 50],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        $keys = [];

        foreach (self::GROUPS as $group => $info) {
            foreach ($info['values'] as $value) {
                $keys[] = $group.'_'.$value;
            }
        }

        return $keys;
    }

    public static function valueOf(string $key): ?int
    {
        foreach (self::GROUPS as $group => $info) {
            foreach ($info['values'] as $value) {
                if ($key === $group.'_'.$value) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, int|string|null>  $breakdown
     */
    public static function total(array $breakdown): int
    {
        $total = 0;

        foreach ($breakdown as $key => $count) {
            $value = self::valueOf((string) $key);

            if ($value !== null) {
                $total += $value * max(0, (int) $count);
            }
        }

        return $total;
    }

    /**
     * @param  array<string, int|string|null>  $breakdown
     * @return array<string, int>
     */
    public static function normalize(array $breakdown): array
    {
        $normalized = [];

        foreach (self::keys() as $key) {
            $count = max(0, (int) ($breakdown[$key] ?? 0));

            if ($count > 0) {
                $normalized[$key] = $count;
            }
        }

        return $normalized;
    }

    public static function label(int $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
