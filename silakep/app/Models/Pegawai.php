<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\Golongan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Pegawai extends Model
{
    protected $fillable = [
        'nip', 'nama', 'gelar_depan', 'gelar_belakang', 'tempat_lahir', 'tanggal_lahir',
        'jenis_kelamin', 'jenis_asn', 'golongan', 'tmt_golongan', 'jabatan', 'opd_id',
        'tmt_kgb_terakhir', 'mkg_tahun', 'mkg_bulan', 'gaji_pokok', 'nilai_skp', 'tahun_skp',
        'hukdis_tunda_kgb_sampai', 'no_hp', 'aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tmt_golongan' => 'date',
            'tmt_kgb_terakhir' => 'date',
            'hukdis_tunda_kgb_sampai' => 'date',
            'mkg_tahun' => 'integer',
            'mkg_bulan' => 'integer',
            'gaji_pokok' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function usulanKgbs(): HasMany
    {
        return $this->hasMany(UsulanKgb::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class);
    }

    protected function namaLengkap(): Attribute
    {
        return Attribute::get(fn () => trim(
            ($this->gelar_depan ? $this->gelar_depan.' ' : '').$this->nama.($this->gelar_belakang ? ', '.$this->gelar_belakang : '')
        ));
    }

    protected function pangkat(): Attribute
    {
        return Attribute::get(fn () => Golongan::pangkat($this->golongan));
    }

    /** TMT KGB berikutnya = TMT KGB terakhir + 2 tahun. */
    protected function tmtKgbBerikutnya(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->tmt_kgb_terakhir?->copy()->addYears(2));
    }

    /** Batasi data sesuai peran: Admin OPD hanya OPD-nya, ASN hanya dirinya. */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->role->lihatSemuaOpd() => $query,
            $user->role === Role::AdminOpd => $query->where('opd_id', $user->opd_id),
            default => $query->whereKey($user->pegawai_id ?? 0),
        };
    }
}
