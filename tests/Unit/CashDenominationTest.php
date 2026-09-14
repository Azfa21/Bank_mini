<?php

namespace Tests\Unit;

use App\Support\CashDenomination;
use PHPUnit\Framework\TestCase;

class CashDenominationTest extends TestCase
{
    public function test_keys_are_unique(): void
    {
        $keys = CashDenomination::keys();

        $this->assertSame($keys, array_values(array_unique($keys)));
        $this->assertNotEmpty($keys);
    }

    public function test_total_sums_counts_times_value(): void
    {
        $total = CashDenomination::total([
            'kertas_100000' => 2,
            'kertas_50000' => 1,
            'logam_500' => 3,
        ]);

        $this->assertSame(251500, $total);
    }

    public function test_total_ignores_invalid_keys(): void
    {
        $total = CashDenomination::total([
            'kertas_100000' => 1,
            'tidak_ada' => 99,
        ]);

        $this->assertSame(100000, $total);
    }

    public function test_normalize_drops_zero_and_invalid_entries(): void
    {
        $normalized = CashDenomination::normalize([
            'kertas_100000' => 2,
            'kertas_50000' => 0,
            'tidak_ada' => 5,
            'logam_100' => 4,
        ]);

        $this->assertSame([
            'kertas_100000' => 2,
            'logam_100' => 4,
        ], $normalized);
    }

    public function test_value_of_returns_value_or_null(): void
    {
        $this->assertSame(100000, CashDenomination::valueOf('kertas_100000'));
        $this->assertSame(50, CashDenomination::valueOf('logam_50'));
        $this->assertNull(CashDenomination::valueOf('tidak_ada'));
    }
}
