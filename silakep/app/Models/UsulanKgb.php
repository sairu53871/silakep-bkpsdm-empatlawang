<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Support\Golongan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsulanKgb extends Model
{
    protected $fillable = [
        'nomor_usulan', 'pegawai_id', 'opd_id', 'status', 'golongan',
        'tmt_lama', 'mkg_lama_tahun', 'mkg_lama_bulan', 'gaji_lama',
        'tmt_baru', 'mkg_baru_tahun', 'mkg_baru_bulan', 'gaji_baru',
        'nilai_skp', 'catatan_sistem', 'berkas_sk_terakhir', 'berkas_skp', 'catatan',
        'nomor_sk', 'tanggal_sk', 'penandatangan_nama', 'penandatangan_nip',
        'penandatangan_pangkat', 'penandatangan_jabatan', 'token_verifikasi',
        'diajukan_at', 'selesai_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusUsulan::class,
            'tmt_lama' => 'date',
            'tmt_baru' => 'date',
            'tanggal_sk' => 'date',
            'gaji_lama' => 'integer',
            'gaji_baru' => 'integer',
            'catatan_sistem' => 'array',
            'diajukan_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (UsulanKgb $usulan) {
            if (! $usulan->nomor_usulan) {
                $usulan->nomor_usulan = sprintf('KGB-%s-%05d', $usulan->tmt_baru->format('Ym'), $usulan->id);
                $usulan->saveQuietly();
            }
        });
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function riwayats(): HasMany
    {
        return $this->hasMany(UsulanKgbRiwayat::class)->latest('id');
    }

    public function pangkat(): string
    {
        return Golongan::pangkat($this->golongan);
    }

    public function punyaPeringatan(): bool
    {
        return ! empty($this->catatan_sistem);
    }

    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->role->lihatSemuaOpd() => $query,
            $user->role === Role::AdminOpd => $query->where('opd_id', $user->opd_id),
            default => $query->where('pegawai_id', $user->pegawai_id ?? 0),
        };
    }
}
