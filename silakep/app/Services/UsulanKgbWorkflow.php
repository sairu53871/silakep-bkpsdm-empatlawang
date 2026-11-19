<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusUsulan as S;
use App\Models\User;
use App\Models\UsulanKgb;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Alur persetujuan KGB:
 *   Draft/Perbaikan --(Admin OPD: ajukan)--> Diajukan
 *   Diajukan --(Verifikator)--> Terverifikasi | Perbaikan | Ditolak
 *   Terverifikasi --(Kabid)--> Disetujui | Perbaikan
 *   Disetujui --(Kepala BKPSDM: tandatangani)--> Selesai (SK terbit) | Perbaikan
 */
class UsulanKgbWorkflow
{
    public const AKSI = [
        'ajukan' => ['dari' => [S::Draft, S::Perbaikan], 'ke' => S::Diajukan, 'peran' => [Role::AdminOpd]],
        'verifikasi' => ['dari' => [S::Diajukan], 'ke' => S::Terverifikasi, 'peran' => [Role::Verifikator]],
        'setujui' => ['dari' => [S::Terverifikasi], 'ke' => S::Disetujui, 'peran' => [Role::Kabid]],
        'tandatangani' => ['dari' => [S::Disetujui], 'ke' => S::Selesai, 'peran' => [Role::Kepala]],
        'kembalikan' => ['dari' => [S::Diajukan, S::Terverifikasi, S::Disetujui], 'ke' => S::Perbaikan, 'peran' => [Role::Verifikator, Role::Kabid, Role::Kepala]],
        'tolak' => ['dari' => [S::Diajukan], 'ke' => S::Ditolak, 'peran' => [Role::Verifikator]],
    ];

    public function bisa(string $aksi, UsulanKgb $usulan, ?User $user): bool
    {
        $aturan = self::AKSI[$aksi] ?? null;
        if (! $aturan || ! $user || ! in_array($usulan->status, $aturan['dari'], true)) {
            return false;
        }

        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        if (! in_array($user->role, $aturan['peran'], true)) {
            return false;
        }

        // Admin OPD hanya untuk pegawai di OPD-nya
        if ($user->role === Role::AdminOpd && $usulan->opd_id !== $user->opd_id) {
            return false;
        }

        // Kepala hanya mengembalikan usulan pada tahapnya sendiri, dst.
        if ($aksi === 'kembalikan') {
            return match ($user->role) {
                Role::Verifikator => $usulan->status === S::Diajukan,
                Role::Kabid => $usulan->status === S::Terverifikasi,
                Role::Kepala => $usulan->status === S::Disetujui,
                default => false,
            };
        }

        return true;
    }

    public function jalankan(string $aksi, UsulanKgb $usulan, User $user, ?string $catatan = null, bool $notifikasi = true): UsulanKgb
    {
        if (! $this->bisa($aksi, $usulan, $user)) {
            throw new InvalidArgumentException("Aksi '{$aksi}' tidak diizinkan untuk usulan {$usulan->nomor_usulan}.");
        }

        if (in_array($aksi, ['kembalikan', 'tolak'], true) && blank($catatan)) {
            throw new InvalidArgumentException('Alasan wajib diisi.');
        }

        $dari = $usulan->status;
        $ke = self::AKSI[$aksi]['ke'];

        DB::transaction(function () use ($aksi, $usulan, $user, $catatan, $dari, $ke) {
            $data = ['status' => $ke, 'catatan' => $catatan];

            if ($aksi === 'ajukan') {
                $data['diajukan_at'] = now();
            }

            if ($aksi === 'tandatangani') {
                $data += $this->dataPenerbitanSk();
                $data['selesai_at'] = now();
            }

            $usulan->update($data);

            $usulan->riwayats()->create([
                'user_id' => $user->id,
                'status_dari' => $dari,
                'status_ke' => $ke,
                'catatan' => $catatan,
            ]);

            // SK terbit -> data pegawai diperbarui, jadi patokan KGB berikutnya
            if ($aksi === 'tandatangani') {
                $usulan->pegawai->update([
                    'tmt_kgb_terakhir' => $usulan->tmt_baru,
                    'mkg_tahun' => $usulan->mkg_baru_tahun,
                    'mkg_bulan' => $usulan->mkg_baru_bulan,
                    'gaji_pokok' => $usulan->gaji_baru,
                ]);
            }
        });

        if ($notifikasi) {
            $this->kirimNotifikasi($usulan->fresh(['pegawai']), 1);
        }

        return $usulan;
    }

    /** Proses massal. Mengembalikan [jumlah berhasil, daftar gagal]. */
    public function jalankanMassal(string $aksi, iterable $usulans, User $user, ?string $catatan = null): array
    {
        $berhasil = collect();
        $gagal = [];

        foreach ($usulans as $usulan) {
            if (! $this->bisa($aksi, $usulan, $user)) {
                $gagal[] = $usulan->nomor_usulan;

                continue;
            }
            $berhasil->push($this->jalankan($aksi, $usulan, $user, $catatan, false));
        }

        // Notifikasi diringkas agar tidak membanjiri penerima
        $berhasil->groupBy(fn ($u) => $u->opd_id)->each(fn ($grup) => $this->kirimNotifikasi($grup->first(), $grup->count(), $grup));

        return [$berhasil->count(), $gagal];
    }

    protected function dataPenerbitanSk(): array
    {
        $tahun = now()->year;
        $urut = UsulanKgb::query()->whereYear('tanggal_sk', $tahun)->whereNotNull('nomor_sk')->count() + 1;

        return [
            'nomor_sk' => str_replace(['{urut}', '{tahun}'], [str_pad($urut, 4, '0', STR_PAD_LEFT), $tahun], config('silakep.format_nomor_sk')),
            'tanggal_sk' => now()->toDateString(),
            'penandatangan_nama' => config('silakep.penandatangan.nama'),
            'penandatangan_nip' => config('silakep.penandatangan.nip'),
            'penandatangan_pangkat' => config('silakep.penandatangan.pangkat'),
            'penandatangan_jabatan' => config('silakep.penandatangan.jabatan'),
            'token_verifikasi' => (string) Str::uuid(),
        ];
    }

    /** Beri tahu pihak yang bertugas di tahap berikutnya. */
    protected function kirimNotifikasi(UsulanKgb $usulan, int $jumlah, $grup = null): void
    {
        $label = $jumlah > 1 ? "{$jumlah} usulan KGB" : "Usulan KGB {$usulan->nomor_usulan} ({$usulan->pegawai->nama})";

        [$penerima, $judul, $tipe] = match ($usulan->status) {
            S::Diajukan => [User::where('role', Role::Verifikator)->get(), "{$label} menunggu verifikasi", 'info'],
            S::Terverifikasi => [User::where('role', Role::Kabid)->get(), "{$label} menunggu persetujuan", 'info'],
            S::Disetujui => [User::where('role', Role::Kepala)->get(), "{$label} menunggu tanda tangan", 'info'],
            S::Perbaikan => [User::where('role', Role::AdminOpd)->where('opd_id', $usulan->opd_id)->get(), "{$label} dikembalikan untuk perbaikan", 'warning'],
            S::Ditolak => [User::where('role', Role::AdminOpd)->where('opd_id', $usulan->opd_id)->get(), "{$label} ditolak/ditunda", 'danger'],
            S::Selesai => [User::where('role', Role::AdminOpd)->where('opd_id', $usulan->opd_id)->get(), "SK KGB terbit: {$label}", 'success'],
            default => [collect(), '', 'info'],
        };

        // ASN yang bersangkutan selalu ikut diberi tahu
        $pegawaiIds = $grup ? $grup->pluck('pegawai_id') : collect([$usulan->pegawai_id]);
        $asn = User::where('role', Role::Asn)->whereIn('pegawai_id', $pegawaiIds)->get();

        foreach ($penerima as $user) {
            Notification::make()->title($judul)->body($usulan->catatan)->status($tipe)->sendToDatabase($user);
        }

        foreach ($asn as $user) {
            Notification::make()
                ->title('Status KGB Anda: '.$usulan->status->getLabel())
                ->body($usulan->catatan)
                ->status($tipe)
                ->sendToDatabase($user);
        }
    }
}
