<?php

namespace Database\Seeders;

use App\Models\GajiPokok;
use Illuminate\Database\Seeder;

/**
 * Tabel gaji pokok PNS — PERKIRAAN.
 *
 * Nilai awal (MKG terendah) & akhir (MKG tertinggi) tiap golongan mengacu
 * pada Lampiran PP 5/2024; nilai di antaranya DIINTERPOLASI. Sebelum produksi,
 * ganti seluruh isi tabel dengan lampiran PP gaji resmi yang berlaku
 * (menu "Tabel Gaji Pokok" bisa diedit admin).
 */
class GajiPokokSeeder extends Seeder
{
    /** golongan => [daftar MKG, gaji MKG terendah, gaji MKG tertinggi] */
    public static function struktur(): array
    {
        $genap = fn ($a, $b) => range($a, $b, 2);
        $ganjil = fn ($a, $b) => range($a, $b, 2);

        return [
            'I/a' => [$genap(0, 26), 1685700, 2522600],
            'I/b' => [$ganjil(3, 27), 1840800, 2670800],
            'I/c' => [$ganjil(3, 27), 1918700, 2783700],
            'I/d' => [$ganjil(3, 27), 1999900, 2901400],
            'II/a' => [array_merge([0], $ganjil(1, 33)), 2184000, 3643400],
            'II/b' => [$ganjil(3, 33), 2385000, 3797500],
            'II/c' => [$ganjil(3, 33), 2485900, 3958100],
            'II/d' => [$ganjil(3, 33), 2591100, 4125600],
            'III/a' => [$genap(0, 32), 2785700, 4575200],
            'III/b' => [$genap(0, 32), 2903600, 4768800],
            'III/c' => [$genap(0, 32), 3026400, 4970500],
            'III/d' => [$genap(0, 32), 3154400, 5180700],
            'IV/a' => [$genap(0, 32), 3287800, 5399900],
            'IV/b' => [$genap(0, 32), 3426900, 5628300],
            'IV/c' => [$genap(0, 32), 3571900, 5866400],
            'IV/d' => [$genap(0, 32), 3723000, 6114500],
            'IV/e' => [$genap(0, 32), 3880400, 6373200],
        ];
    }

    public function run(): void
    {
        $rows = [];
        $now = now();

        foreach (self::struktur() as $golongan => [$mkgs, $min, $max]) {
            $n = count($mkgs) - 1;
            $rasio = ($max / $min) ** (1 / $n);

            foreach ($mkgs as $i => $mkg) {
                $gaji = $i === $n ? $max : (int) (round($min * $rasio ** $i / 100) * 100);
                $rows[] = [
                    'golongan' => $golongan,
                    'mkg' => $mkg,
                    'gaji' => $gaji,
                    'dasar_hukum' => 'PP 5/2024 (PERKIRAAN – cocokkan dengan lampiran resmi)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        GajiPokok::query()->delete();
        GajiPokok::insert($rows);
    }
}
