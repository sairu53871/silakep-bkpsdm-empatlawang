<?php

namespace App\Filament\Resources\Pegawais\Pages;

use App\Enums\Role;
use App\Filament\Resources\Pegawais\PegawaiResource;
use App\Services\PegawaiImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ListPegawais extends ListRecords
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        $bolehImpor = fn () => auth()->user()->punyaPeran(Role::SuperAdmin, Role::Verifikator);

        return [
            Action::make('template')
                ->label('Template Excel')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible($bolehImpor)
                ->action(fn () => response()->download(app(PegawaiImporter::class)->template(), 'template-impor-pegawai.xlsx')),
            Action::make('impor')
                ->label('Impor Excel')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->visible($bolehImpor)
                ->modalDescription('Unggah file Excel hasil ekspor SIASN/BKPSDM sesuai template. Pegawai dengan NIP yang sudah ada akan diperbarui.')
                ->schema([
                    FileUpload::make('file')->label('File Excel (.xlsx / .xls / .csv)')
                        ->disk('local')->directory('impor')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel', 'text/csv', 'text/plain',
                        ])
                        ->maxSize(10240)->required(),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    $hasil = app(PegawaiImporter::class)->impor($path);
                    Storage::disk('local')->delete($data['file']);

                    $gagal = count($hasil['gagal']);
                    Notification::make()
                        ->title("Impor selesai: {$hasil['baru']} baru, {$hasil['diperbarui']} diperbarui, {$gagal} gagal")
                        ->body($gagal ? implode("\n", array_slice($hasil['gagal'], 0, 8)).($gagal > 8 ? "\n…" : '') : null)
                        ->status($gagal ? 'warning' : 'success')
                        ->persistent()
                        ->send();
                }),
            CreateAction::make()->label('Tambah Pegawai'),
        ];
    }
}
