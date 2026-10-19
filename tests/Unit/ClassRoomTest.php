<?php

namespace Tests\Unit;

use App\Support\ClassRoom;
use PHPUnit\Framework\TestCase;

/**
 * Aturan penamaan kelas dipakai bersama oleh form anggota, aktivasi loket, dan kenaikan
 * kelas. Bagian murni (urai, susun, tingkat berikutnya) diuji tanpa basis data.
 */
class ClassRoomTest extends TestCase
{
    public function test_promote_raises_only_the_level(): void
    {
        $this->assertSame('XI RPL 1', ClassRoom::promote('X RPL 1'));
        $this->assertSame('XII TKJ 2', ClassRoom::promote('XI TKJ 2'));
        $this->assertSame('XI RPL 12', ClassRoom::promote('X RPL 12'));
    }

    public function test_promote_accepts_lowercase_and_extra_spaces(): void
    {
        $this->assertSame('XI RPL 2', ClassRoom::promote(' x   rpl 2 '));
    }

    public function test_promote_keeps_class_without_rombel_number(): void
    {
        $this->assertSame('XI RPL', ClassRoom::promote('X RPL'));
    }

    public function test_promote_refuses_final_year_and_unknown_classes(): void
    {
        $this->assertNull(ClassRoom::promote('XII RPL 1'));
        $this->assertNull(ClassRoom::promote('VII A'));
        $this->assertNull(ClassRoom::promote('10 RPL 1'));
        $this->assertNull(ClassRoom::promote('X'));
        $this->assertNull(ClassRoom::promote(''));
        $this->assertNull(ClassRoom::promote(null));
    }

    public function test_parse_splits_level_major_and_rombel(): void
    {
        $this->assertSame(
            ['level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
            ClassRoom::parse('XI RPL 1'),
        );

        $this->assertSame(
            ['level' => 'XII', 'major' => 'LPS', 'rombel' => '10'],
            ClassRoom::parse('xii lps 10'),
        );

        $this->assertSame(
            ['level' => null, 'major' => 'VII A', 'rombel' => null],
            ClassRoom::parse('VII A'),
        );
    }

    public function test_next_level_follows_the_school_levels(): void
    {
        $this->assertSame('XI', ClassRoom::nextLevel('X'));
        $this->assertSame('XII', ClassRoom::nextLevel('xi'));
        $this->assertNull(ClassRoom::nextLevel('XII'));
        $this->assertNull(ClassRoom::nextLevel(null));
    }

    public function test_compose_builds_a_consistent_class_name(): void
    {
        $this->assertSame('XI RPL 2', ClassRoom::compose(' xi ', 'rpl', '2'));
        $this->assertSame('X RPL', ClassRoom::compose('X', 'RPL', null));
        $this->assertSame('XII TKJ 1', ClassRoom::compose('XII', 'TKJ', 1));
    }
}
