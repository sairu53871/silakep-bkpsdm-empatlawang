<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GajiPokok extends Model
{
    protected $fillable = ['golongan', 'mkg', 'gaji', 'dasar_hukum'];

    protected function casts(): array
    {
        return ['mkg' => 'integer', 'gaji' => 'integer'];
    }

    /**
     * Cari gaji pokok untuk golongan & MKG tertentu.
     * Tabel PP gaji hanya memuat MKG tertentu (tiap 2 tahun), jadi diambil
     * baris dengan MKG terbesar yang <= MKG pegawai.
     */
    public static function cari(string $golongan, int $mkg): ?self
    {
        return static::query()
            ->where('golongan', $golongan)
            ->where('mkg', '<=', $mkg)
            ->orderByDesc('mkg')
            ->first();
    }

    public static function mkgMaksimal(string $golongan): ?int
    {
        return static::query()->where('golongan', $golongan)->max('mkg');
    }
}
