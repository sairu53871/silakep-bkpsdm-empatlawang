<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusUsulan: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case Perbaikan = 'perbaikan';
    case Terverifikasi = 'terverifikasi';
    case Disetujui = 'disetujui';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Menunggu Pengajuan OPD',
            self::Diajukan => 'Menunggu Verifikasi',
            self::Perbaikan => 'Perlu Perbaikan',
            self::Terverifikasi => 'Menunggu Persetujuan Kabid',
            self::Disetujui => 'Menunggu TTD Kepala BKPSDM',
            self::Selesai => 'SK Terbit',
            self::Ditolak => 'Ditolak / Ditunda',
        };
    }

    public function labelSingkat(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Diajukan => 'Diajukan',
            self::Perbaikan => 'Perbaikan',
            self::Terverifikasi => 'Terverifikasi',
            self::Disetujui => 'Disetujui',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Diajukan, self::Terverifikasi, self::Disetujui => 'info',
            self::Perbaikan => 'warning',
            self::Selesai => 'success',
            self::Ditolak => 'danger',
        };
    }

    /** Urutan tahap untuk tampilan timeline lacak. */
    public function tahap(): int
    {
        return match ($this) {
            self::Draft, self::Perbaikan => 1,
            self::Diajukan => 2,
            self::Terverifikasi => 3,
            self::Disetujui => 4,
            self::Selesai, self::Ditolak => 5,
        };
    }

    public function aktif(): bool
    {
        return ! in_array($this, [self::Selesai, self::Ditolak], true);
    }
}
