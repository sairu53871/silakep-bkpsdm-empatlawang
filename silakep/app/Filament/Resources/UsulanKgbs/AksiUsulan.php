<?php

namespace App\Filament\Resources\UsulanKgbs;

use App\Enums\Role;
use App\Enums\StatusUsulan;
use App\Models\UsulanKgb;
use App\Services\KgbService;
use App\Services\SkKgbPdf;
use App\Services\UsulanKgbWorkflow;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Kumpulan tombol aksi alur KGB, dipakai di tabel (per baris & massal) dan halaman detail.
 */
class AksiUsulan
{
    /** label, warna, ikon, pesan konfirmasi */
    protected const TAMPILAN = [
        'ajukan' => ['Ajukan ke BKPSDM', 'primary', Heroicon::OutlinedPaperAirplane, 'Usulan akan dikirim ke verifikator BKPSDM.'],
        'verifikasi' => ['Verifikasi', 'info', Heroicon::OutlinedCheckBadge, 'Nyatakan berkas lengkap & memenuhi syarat?'],
        'setujui' => ['Setujui', 'success', Heroicon::OutlinedHandThumbUp, 'Setujui usulan dan teruskan ke Kepala BKPSDM?'],
        'tandatangani' => ['Tandatangani & Terbitkan SK', 'success', Heroicon::OutlinedPencilSquare, 'SK KGB akan terbit (nomor SK & QR verifikasi dibuat otomatis) dan data gaji pegawai diperbarui.'],
        'kembalikan' => ['Kembalikan untuk Perbaikan', 'warning', Heroicon::OutlinedArrowUturnLeft, null],
        'tolak' => ['Tolak / Tunda', 'danger', Heroicon::OutlinedXCircle, null],
    ];

    protected static function wf(): UsulanKgbWorkflow
    {
        return app(UsulanKgbWorkflow::class);
    }

    protected static function formAksi(string $aksi): array
    {
        $form = [];

        if ($aksi === 'ajukan') {
            $form[] = FileUpload::make('berkas_sk_terakhir')
                ->label('SK KGB / SK Pangkat terakhir (PDF, opsional)')
                ->disk('local')->directory('berkas-kgb')->visibility('private')
                ->acceptedFileTypes(['application/pdf'])->maxSize(2048);
            $form[] = FileUpload::make('berkas_skp')
                ->label('SKP / Penilaian Kinerja terakhir (PDF, opsional)')
                ->disk('local')->directory('berkas-kgb')->visibility('private')
                ->acceptedFileTypes(['application/pdf'])->maxSize(2048);
        }

        $wajib = in_array($aksi, ['kembalikan', 'tolak'], true);
        $form[] = Textarea::make('catatan')
            ->label($wajib ? 'Alasan' : 'Catatan (opsional)')
            ->required($wajib)
            ->rows(3);

        return $form;
    }

    public static function make(string $aksi): Action
    {
        [$label, $warna, $ikon, $konfirmasi] = self::TAMPILAN[$aksi];

        return Action::make($aksi)
            ->label($label)
            ->color($warna)
            ->icon($ikon)
            ->visible(fn (UsulanKgb $record) => self::wf()->bisa($aksi, $record, auth()->user()))
            ->modalHeading(fn (UsulanKgb $record) => "{$label}: {$record->nomor_usulan}")
            ->modalDescription($konfirmasi)
            ->schema(self::formAksi($aksi))
            ->action(function (UsulanKgb $record, array $data) use ($aksi) {
                if ($aksi === 'ajukan') {
                    $record->update(array_filter([
                        'berkas_sk_terakhir' => $data['berkas_sk_terakhir'] ?? null,
                        'berkas_skp' => $data['berkas_skp'] ?? null,
                    ]));
                }

                try {
                    self::wf()->jalankan($aksi, $record, auth()->user(), $data['catatan'] ?? null);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Berhasil: '.$record->fresh()->status->getLabel())->success()->send();
            });
    }

    public static function massal(string $aksi): BulkAction
    {
        [$label, $warna, $ikon, $konfirmasi] = self::TAMPILAN[$aksi];
        $peran = UsulanKgbWorkflow::AKSI[$aksi]['peran'];

        return BulkAction::make($aksi.'_massal')
            ->label($label.' (terpilih)')
            ->color($warna)
            ->icon($ikon)
            ->visible(fn () => auth()->user()->punyaPeran(Role::SuperAdmin, ...$peran))
            ->requiresConfirmation()
            ->modalDescription(($konfirmasi ?? '').' Usulan yang tidak sesuai tahap akan dilewati.')
            ->schema(in_array($aksi, ['kembalikan', 'tolak'], true) ? self::formAksi($aksi) : [])
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data) use ($aksi) {
                [$jumlah, $gagal] = self::wf()->jalankanMassal($aksi, $records, auth()->user(), $data['catatan'] ?? null);

                Notification::make()
                    ->title("{$jumlah} usulan berhasil diproses")
                    ->body($gagal ? count($gagal).' dilewati (tahap/kewenangan tidak sesuai): '.implode(', ', array_slice($gagal, 0, 5)).(count($gagal) > 5 ? ', …' : '') : null)
                    ->status($jumlah ? 'success' : 'warning')
                    ->send();
            });
    }

    public static function unduhSk(): Action
    {
        return Action::make('unduh_sk')
            ->label('Unduh SK')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (UsulanKgb $record) => $record->status === StatusUsulan::Selesai)
            ->url(fn (UsulanKgb $record) => route('sk-kgb.unduh', $record), shouldOpenInNewTab: true);
    }

    public static function unduhSkMassal(): BulkAction
    {
        return BulkAction::make('cetak_sk_massal')
            ->label('Cetak SK terpilih (1 PDF)')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            ->action(function (Collection $records) {
                $selesai = $records->filter(fn ($r) => $r->status === StatusUsulan::Selesai);
                if ($selesai->isEmpty()) {
                    Notification::make()->title('Tidak ada SK terbit pada pilihan Anda.')->warning()->send();

                    return null;
                }

                return response()->streamDownload(
                    fn () => print (app(SkKgbPdf::class)->buat($selesai)->output()),
                    'SK-KGB-'.now()->format('Ymd-His').'.pdf'
                );
            });
    }

    public static function hitungUlang(): Action
    {
        return Action::make('hitung_ulang')
            ->label('Hitung Ulang')
            ->icon(Heroicon::OutlinedCalculator)
            ->color('gray')
            ->visible(fn (UsulanKgb $record) => in_array($record->status, [StatusUsulan::Draft, StatusUsulan::Perbaikan, StatusUsulan::Diajukan], true)
                && auth()->user()->punyaPeran(Role::SuperAdmin, Role::AdminOpd, Role::Verifikator))
            ->requiresConfirmation()
            ->modalDescription('Hitung ulang TMT, MKG, dan gaji baru dari data pegawai terbaru (gunakan setelah data pegawai diperbaiki).')
            ->action(function (UsulanKgb $record) {
                app(KgbService::class)->hitungUlang($record);
                Notification::make()->title('Usulan dihitung ulang.')->success()->send();
            });
    }
}
