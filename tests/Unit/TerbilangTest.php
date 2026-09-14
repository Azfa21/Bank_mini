<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    public function test_zero(): void
    {
        $this->assertSame('Nol Rupiah', Terbilang::rupiah(0));
    }

    public function test_teens_and_units(): void
    {
        $this->assertSame('Sebelas Rupiah', Terbilang::rupiah(11));
        $this->assertSame('Sembilan Rupiah', Terbilang::rupiah(9));
    }

    public function test_puluhan_tanpa_nol_trailing(): void
    {
        $this->assertSame('Lima Puluh Ribu Rupiah', Terbilang::rupiah(50000));
        $this->assertSame('Dua Belas Ribu Rupiah', Terbilang::rupiah(12000));
        $this->assertSame('Dua Juta Rupiah', Terbilang::rupiah(2000000));
    }

    public function test_seratus_seribu_seratus_ribu(): void
    {
        $this->assertSame('Seratus Rupiah', Terbilang::rupiah(100));
        $this->assertSame('Seribu Rupiah', Terbilang::rupiah(1000));
        $this->assertSame('Seratus Ribu Rupiah', Terbilang::rupiah(100000));
        $this->assertSame('Seribu Lima Ratus Rupiah', Terbilang::rupiah(1500));
    }

    public function test_kombinasi_lengkap(): void
    {
        $this->assertSame(
            'Satu Juta Dua Ratus Tiga Puluh Empat Ribu Lima Ratus Enam Puluh Tujuh Rupiah',
            Terbilang::rupiah(1234567)
        );
    }

    public function test_negatif(): void
    {
        $this->assertSame('Minus Lima Ribu Rupiah', Terbilang::rupiah(-5000));
    }
}
