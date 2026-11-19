<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opd extends Model
{
    protected $fillable = ['kode', 'nama', 'singkatan'];

    public function pegawais(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }

    public function usulanKgbs(): HasMany
    {
        return $this->hasMany(UsulanKgb::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
