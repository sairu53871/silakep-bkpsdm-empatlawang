# SILAKEP — MVP Layanan KGB Online

Sistem Layanan Kepegawaian Online BKPSDM Kabupaten Empat Lawang.
**Versi MVP: khusus Kenaikan Gaji Berkala (KGB).** Rujukan kebutuhan: [`../BRIEF.md`](../BRIEF.md).

> ⚠️ Semua data pegawai, nama, NIP, alamat kantor, nama Kepala BKPSDM, dan tabel gaji di aplikasi ini adalah **DUMMY / PERKIRAAN** untuk demo.

---

## Instalasi pertama (setelah clone dari GitHub)

Prasyarat: PHP 8.3 (ekstensi `intl`, `zip`, `gd`, `pdo_sqlite`) dan Composer.

```bash
cd silakep
composer install
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed   # membuat database/database.sqlite + data dummy
php artisan serve
```

## Menjalankan (Windows)

Prasyarat: PHP 8.3 (ekstensi `intl`, `zip`, `gd`, `pdo_sqlite` aktif). Composer ada di `%USERPROFILE%\tools\composer.phar`.

- **Klik dua kali `jalankan.bat`**, lalu buka http://localhost:8000
- **`reset-data-demo.bat`** — hapus semua data & isi ulang data dummy (usulan dideteksi ulang dari tanggal hari ini)

Manual:
```bash
php artisan migrate:fresh --seed   # buat database + data dummy
php artisan serve                  # http://localhost:8000
php artisan test                   # jalankan 21 test otomatis
php artisan kgb:deteksi            # deteksi KGB jatuh tempo (otomatis tiap hari 01.00 via scheduler)
```

## Akun demo (password semua: `password`)

| Peran | Username | Yang bisa dicoba |
|---|---|---|
| Super Admin | `admin` | Semua menu, tabel gaji, OPD, pengguna |
| Admin OPD | `opd.dikbud`, `opd.dinkes`, `opd.<kode-opd>` | Ajukan usulan pegawai OPD-nya (bisa massal), upload berkas |
| Verifikator BKPSDM | `verifikator` | Verifikasi / kembalikan / tolak, impor Excel pegawai, deteksi KGB |
| Kepala Bidang | `kabid` | Setujui usulan |
| Kepala BKPSDM | `kepala` | Tandatangani & terbitkan SK (bisa massal), cetak SK |
| ASN | NIP pegawai demo (lihat menu Pengguna, peran ASN) | Lihat KGB sendiri, status, unduh SK |

## Skenario demo (± 5 menit)

1. Buka http://localhost:8000 → halaman publik (lacak status & cek keaslian SK).
2. Login `opd.dikbud` → menu **Usulan KGB** → tab **Tugas Saya** → pilih beberapa → *Proses Massal → Ajukan ke BKPSDM*.
3. Login `verifikator` → **Tugas Saya** → buka satu usulan → lihat data lama vs baru, peringatan sistem → *Verifikasi*.
4. Login `kabid` → *Setujui*. Login `kepala` → *Tandatangani & Terbitkan SK* → **Unduh SK** (PDF + QR code).
5. Pindai QR / buka link di kaki SK → halaman **SK terdaftar dan sah**.
6. Lacak status di halaman publik pakai nomor usulan + NIP.
7. Lonceng 🔔 di kanan atas = notifikasi tiap tahap.

## Yang sudah ada di MVP

- Deteksi otomatis KGB jatuh tempo (≤ 3 bulan ke depan + yang terlewat), hanya PNS aktif, tanpa duplikasi
- Hitung otomatis: TMT baru (+2 tahun), MKG baru (+2), gaji pokok baru dari tabel
- Peringatan otomatis: SKP di bawah "Baik", hukdis penundaan KGB, MKG sudah maksimal
- Alur: Admin OPD → Verifikator → Kabid → Kepala BKPSDM, bisa dikembalikan (alasan wajib) / ditolak
- Proses **massal** per tahap + cetak banyak SK jadi 1 PDF
- SK KGB PDF otomatis (nomor SK otomatis, QR verifikasi), data pegawai ter-update saat SK terbit
- Riwayat/tracking tiap usulan, notifikasi di aplikasi, dashboard (statistik, usulan mendesak, rekap per OPD)
- Hak akses per peran (Admin OPD hanya OPD-nya, ASN hanya dirinya)
- Impor data pegawai dari Excel (template tersedia di menu Data Pegawai) — upsert berdasarkan NIP
- Halaman publik: lacak status (wajib nomor usulan + NIP) & verifikasi keaslian SK (NIP disamarkan)

## Asumsi & nilai PERKIRAAN yang harus diganti sebelum produksi

| Item | Lokasi | Keterangan |
|---|---|---|
| Tabel gaji pokok | Menu *Pengaturan → Tabel Gaji Pokok* / `database/seeders/GajiPokokSeeder.php` | Nilai awal & akhir per golongan mengacu PP 5/2024, nilai tengah **diinterpolasi**. Cocokkan dengan lampiran resmi. |
| Nama/NIP/pangkat Kepala BKPSDM | `.env` → `SILAKEP_KEPALA_*` | Dummy |
| Alamat kantor (kop) | `.env` → `SILAKEP_ALAMAT` | Dummy |
| Format nomor SK | `.env` → `SILAKEP_FORMAT_NOMOR_SK` | Perkiraan `800.1.11.1/{urut}/KGB/BKPSDM/{tahun}`; sesuaikan tata naskah dinas |
| Format kolom Excel | `app/Services/PegawaiImporter.php` | Perkiraan; sesuaikan setelah ada contoh ekspor SIASN/BKPSDM |
| Isi surat SK KGB | `resources/views/pdf/sk-kgb.blade.php` | Mengikuti format umum surat pemberitahuan KGB; sesuaikan dengan template Empat Lawang |
| Daftar OPD | `database/seeders/DatabaseSeeder.php` | 28 OPD perkiraan + 10 kecamatan; sesuaikan dengan Perda SOTK |
| PPPK | — | Belum diproses KGB otomatis (tabel gaji PPPK berbeda) |

## Belum termasuk (tahap berikutnya, sesuai brief)

- TTE BSrE (sudah disiapkan: `SILAKEP_TTE_AKTIF=true` mengubah blok tanda tangan; integrasi API menyusul)
- Notifikasi WhatsApp/email, integrasi API SIASN, server online, modul layanan lain (tubel, penyetaraan gelar, dst.)

## Catatan teknis untuk Diskominfo

- Laravel 13 + Filament 5 + SQLite (dev). Produksi: ganti `DB_CONNECTION=mysql`.
- `QUEUE_CONNECTION=sync` untuk MVP; produksi sebaiknya `database` + worker.
- Scheduler: jalankan `php artisan schedule:run` tiap menit (cron/Task Scheduler) agar deteksi KGB harian berjalan.
- Berkas upload disimpan privat di `storage/app/private/berkas-kgb`, diakses lewat route yang dicek hak aksesnya.
- Logika inti: `app/Services/KgbService.php` (hitung & deteksi), `app/Services/UsulanKgbWorkflow.php` (alur & hak aksi).
