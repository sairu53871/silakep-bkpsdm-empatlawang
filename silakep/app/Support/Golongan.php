<?php

namespace App\Support;

class Golongan
{
    public const PANGKAT = [
        'I/a' => 'Juru Muda', 'I/b' => 'Juru Muda Tingkat I', 'I/c' => 'Juru', 'I/d' => 'Juru Tingkat I',
        'II/a' => 'Pengatur Muda', 'II/b' => 'Pengatur Muda Tingkat I', 'II/c' => 'Pengatur', 'II/d' => 'Pengatur Tingkat I',
        'III/a' => 'Penata Muda', 'III/b' => 'Penata Muda Tingkat I', 'III/c' => 'Penata', 'III/d' => 'Penata Tingkat I',
        'IV/a' => 'Pembina', 'IV/b' => 'Pembina Tingkat I', 'IV/c' => 'Pembina Utama Muda', 'IV/d' => 'Pembina Utama Madya', 'IV/e' => 'Pembina Utama',
    ];

    /** @return array<string,string> golongan => "Penata Muda (III/a)" */
    public static function opsi(): array
    {
        return collect(self::PANGKAT)->mapWithKeys(fn ($p, $g) => [$g => "{$g} – {$p}"])->all();
    }

    public static function pangkat(?string $golongan): string
    {
        return self::PANGKAT[$golongan] ?? '-';
    }

    public static function valid(?string $golongan): bool
    {
        return isset(self::PANGKAT[$golongan]);
    }

    public static function rupiah(?int $nilai): string
    {
        return 'Rp '.number_format((int) $nilai, 0, ',', '.');
    }

    public static function mkg(int $tahun, int $bulan = 0): string
    {
        return "{$tahun} tahun {$bulan} bulan";
    }
}
