<?php

namespace App\Services;

use App\Models\UsulanKgb;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SkKgbPdf
{
    /** @param Collection<int, UsulanKgb>|UsulanKgb $usulans */
    public function buat(Collection|UsulanKgb $usulans): DomPdf
    {
        $usulans = new EloquentCollection($usulans instanceof UsulanKgb ? [$usulans] : $usulans->values()->all());
        $usulans->loadMissing(['pegawai', 'opd']);

        $qr = $usulans->mapWithKeys(fn (UsulanKgb $u) => [
            $u->id => 'data:image/svg+xml;base64,'.base64_encode(
                (string) QrCode::format('svg')->size(220)->margin(0)->errorCorrection('M')
                    ->generate(route('verifikasi.sk', $u->token_verifikasi))
            ),
        ]);

        return Pdf::loadView('pdf.sk-kgb', [
            'usulans' => $usulans,
            'qr' => $qr,
            'instansi' => config('silakep.instansi'),
            'tteAktif' => (bool) config('silakep.tte_aktif'),
        ])->setPaper('a4');
    }

    public static function namaFile(UsulanKgb $usulan): string
    {
        return 'SK-KGB-'.$usulan->pegawai->nip.'-'.$usulan->tmt_baru->format('Ymd').'.pdf';
    }
}
