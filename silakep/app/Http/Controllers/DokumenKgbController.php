<?php

namespace App\Http\Controllers;

use App\Enums\StatusUsulan;
use App\Filament\Resources\UsulanKgbs\UsulanKgbResource;
use App\Models\UsulanKgb;
use App\Services\SkKgbPdf;
use Illuminate\Support\Facades\Storage;

/** Unduh SK & berkas — hanya untuk user login yang berhak melihat usulan tsb. */
class DokumenKgbController extends Controller
{
    protected function izinkan(UsulanKgb $usulan): void
    {
        abort_unless(
            UsulanKgb::query()->terlihatOleh(auth()->user())->whereKey($usulan->id)->exists(),
            403
        );
    }

    public function unduhSk(UsulanKgb $usulan, SkKgbPdf $pdf)
    {
        $this->izinkan($usulan);
        abort_unless($usulan->status === StatusUsulan::Selesai, 404, 'SK belum terbit.');

        return $pdf->buat($usulan)->stream(SkKgbPdf::namaFile($usulan));
    }

    public function lihatBerkas(UsulanKgb $usulan, string $jenis)
    {
        $this->izinkan($usulan);
        $path = UsulanKgbResource::berkasPath($usulan, $jenis);
        abort_unless($path, 404);

        return Storage::disk('local')->response($path);
    }
}
