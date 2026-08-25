<?php

namespace Tests\Unit;

use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SequenceService
    {
        return app(SequenceService::class);
    }

    public function test_next_returns_incrementing_numbers_per_key(): void
    {
        $this->assertSame(1, $this->service()->next('TRX-TEST'));
        $this->assertSame(2, $this->service()->next('TRX-TEST'));
        $this->assertSame(3, $this->service()->next('TRX-TEST'));
    }

    public function test_keys_have_independent_counters(): void
    {
        $this->assertSame(1, $this->service()->next('KEY-A'));
        $this->assertSame(1, $this->service()->next('KEY-B'));
        $this->assertSame(2, $this->service()->next('KEY-A'));
        $this->assertSame(2, $this->service()->next('KEY-B'));
    }

    public function test_floor_seeds_counter_above_legacy_numbers(): void
    {
        $this->assertSame(6, $this->service()->next('ACC-LEGACY', 5));
        $this->assertSame(7, $this->service()->next('ACC-LEGACY'));
        $this->assertSame(8, $this->service()->next('ACC-LEGACY'));
    }

    public function test_floor_is_ignored_when_sequence_already_ahead(): void
    {
        $this->service()->next('ACC-AHEAD');
        $this->service()->next('ACC-AHEAD');

        $this->assertSame(3, $this->service()->next('ACC-AHEAD', 1));
        $this->assertSame(4, $this->service()->next('ACC-AHEAD', 2));
    }

    public function test_formatted_pads_number_with_prefix(): void
    {
        $this->assertSame('INV-0001', $this->service()->formatted('seq-inv-test', 'INV', 4));
        $this->assertSame('INV-0002', $this->service()->formatted('seq-inv-test', 'INV', 4));
    }
}
