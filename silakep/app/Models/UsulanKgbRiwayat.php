<?php

namespace App\Models;

use App\Enums\StatusUsulan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsulanKgbRiwayat extends Model
{
    protected $fillable = ['usulan_kgb_id', 'user_id', 'status_dari', 'status_ke', 'catatan'];

    protected function casts(): array
    {
        return [
            'status_dari' => StatusUsulan::class,
            'status_ke' => StatusUsulan::class,
        ];
    }

    public function usulanKgb(): BelongsTo
    {
        return $this->belongsTo(UsulanKgb::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
