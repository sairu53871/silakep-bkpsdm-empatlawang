<?php

namespace App\Http\Controllers;

use App\Enums\StatusUsulan;
use App\Models\UsulanKgb;
use Illuminate\Http\Request;

/** Halaman tanpa login: lacak status pengajuan & verifikasi keaslian SK. */
class PublikController extends Controller
{
    public function beranda()
    {
        return view('publik.beranda');
    }

    public function lacak(Request $request)
    {
        $data = $request->validate([
            'nomor' => ['required', 'string', 'max:30'],
            'nip' => ['required', 'digits:18'],
        ], [
            'nomor.required' => 'Nomor usulan wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'nip.digits' => 'NIP harus 18 digit angka.',
        ]);

        // Wajib cocok nomor usulan + NIP supaya data orang lain tidak bisa diintip
        $usulan = UsulanKgb::query()
            ->with(['pegawai', 'riwayats'])
            ->where('nomor_usulan', strtoupper(trim($data['nomor'])))
            ->whereHas('pegawai', fn ($q) => $q->where('nip', $data['nip']))
            ->first();

        return view('publik.lacak', [
            'usulan' => $usulan,
            'tahapan' => [
                1 => 'Pengajuan oleh OPD',
                2 => 'Verifikasi BKPSDM',
                3 => 'Persetujuan Kepala Bidang',
                4 => 'Tanda Tangan Kepala BKPSDM',
                5 => 'SK Terbit',
            ],
        ]);
    }

    public function cariSk(Request $request)
    {
        $data = $request->validate(['nomor_sk' => ['required', 'string', 'max:100']], ['nomor_sk.required' => 'Nomor SK wajib diisi.']);

        $usulan = UsulanKgb::query()
            ->where('nomor_sk', trim($data['nomor_sk']))
            ->where('status', StatusUsulan::Selesai)
            ->first();

        return $usulan
            ? redirect()->route('verifikasi.sk', $usulan->token_verifikasi)
            : view('publik.verifikasi', ['usulan' => null]);
    }

    public function verifikasi(string $token)
    {
        $usulan = UsulanKgb::query()
            ->with(['pegawai', 'opd'])
            ->where('token_verifikasi', $token)
            ->where('status', StatusUsulan::Selesai)
            ->first();

        return view('publik.verifikasi', ['usulan' => $usulan]);
    }
}
