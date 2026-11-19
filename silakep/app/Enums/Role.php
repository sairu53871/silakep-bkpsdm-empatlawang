<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case SuperAdmin = 'super_admin';
    case AdminOpd = 'admin_opd';
    case Verifikator = 'verifikator';
    case Kabid = 'kabid';
    case Kepala = 'kepala';
    case Asn = 'asn';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminOpd => 'Admin OPD',
            self::Verifikator => 'Verifikator BKPSDM',
            self::Kabid => 'Kepala Bidang',
            self::Kepala => 'Kepala BKPSDM',
            self::Asn => 'ASN',
        };
    }

    /** Peran internal BKPSDM yang boleh melihat data seluruh OPD. */
    public function lihatSemuaOpd(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Verifikator, self::Kabid, self::Kepala], true);
    }
}
