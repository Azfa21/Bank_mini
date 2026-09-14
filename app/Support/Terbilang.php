<?php

namespace App\Support;

class Terbilang
{
    /**
     * @var array<int, string>
     */
    private const SATUAN = [
        'Nol', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
        'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
    ];

    public static function rupiah(int $amount): string
    {
        $words = self::convert(abs($amount));

        return $amount < 0 ? 'Minus '.$words.' Rupiah' : $words.' Rupiah';
    }

    public static function convert(int $number): string
    {
        $number = abs($number);

        if ($number === 0) {
            return 'Nol';
        }

        return self::words($number);
    }

    /**
     * Mengubah angka menjadi kata tanpa menyisakan komponen nol.
     */
    private static function words(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        if ($number < 12) {
            return self::SATUAN[$number];
        }

        if ($number < 20) {
            return trim(self::SATUAN[$number - 10].' Belas');
        }

        if ($number < 100) {
            return trim(self::SATUAN[intdiv($number, 10)].' Puluh '.self::words($number % 10));
        }

        if ($number < 200) {
            return trim('Seratus '.self::words($number - 100));
        }

        if ($number < 1000) {
            return trim(self::SATUAN[intdiv($number, 100)].' Ratus '.self::words($number % 100));
        }

        if ($number < 2000) {
            return trim('Seribu '.self::words($number - 1000));
        }

        if ($number < 1000000) {
            return trim(self::words(intdiv($number, 1000)).' Ribu '.self::words($number % 1000));
        }

        if ($number < 1000000000) {
            return trim(self::words(intdiv($number, 1000000)).' Juta '.self::words($number % 1000000));
        }

        if ($number < 1000000000000) {
            return trim(self::words(intdiv($number, 1000000000)).' Miliar '.self::words($number % 1000000000));
        }

        return trim(self::words(intdiv($number, 1000000000000)).' Triliun '.self::words($number % 1000000000000));
    }
}
