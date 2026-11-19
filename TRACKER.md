# Tracker Project SILAKEP — BKPSDM Kabupaten Empat Lawang

> Dokumen pemantauan perubahan, keputusan, dan pekerjaan yang tersisa.
> Rujukan kebutuhan: [BRIEF.md](BRIEF.md) · Aplikasi: [silakep/](silakep/README.md)
> Terakhir diperbarui: **19 November 2026**

**Cara pakai:** setiap ada perubahan, tambahkan baris di **Bagian 2 (Log Perubahan)**, ditulis paling baru di atas. Perbarui juga status di Bagian 1, centang checklist di Bagian 4, dan pindahkan pertanyaan yang sudah terjawab ke Bagian 3.

---

## 1. Status Modul

| Modul | Prioritas | Status | Catatan |
|---|---|---|---|
| Kenaikan Gaji Berkala (KGB) | P1 | 🟡 **MVP jalan (data dummy)** | Siap demo. Belum siap produksi (lihat Bagian 4). |
| Tugas belajar & perpanjangan tubel | P1 | ⚪ Belum mulai | |
| Penyetaraan / pencantuman gelar | P1 | ⚪ Belum mulai | |
| Izin belajar | P1 | ⚪ Belum mulai | |
| Mutasi dalam daerah | P2 | ⚪ Belum mulai | Perlu analisis alur (melibatkan Bupati/PPK) |
| Izin cuti, surat keterangan, KP, pensiun | P2 | ⚪ Belum mulai | |
| Layanan prioritas 3 | P3 | ⚪ Belum mulai | |
| Integrasi TTE BSrE | — | ⚪ Menunggu | Kerja sama BSrE masih diproses. Aplikasi sudah siap TTE. |
| Notifikasi WhatsApp | — | ⚪ Belum mulai | |
| Integrasi API SIASN | — | ⚪ Belum mulai | Sementara pakai impor Excel |
| Server produksi (Diskominfo) | — | ⚪ Belum mulai | Masih berjalan di laptop |

Keterangan: ⚪ belum mulai · 🔵 sedang dikerjakan · 🟡 jalan, belum final · 🟢 selesai/produksi · 🔴 terhambat

---

## 2. Log Perubahan

| Tanggal | Bagian | Perubahan | Oleh |
|---|---|---|---|
| 2026-11-19 | Repositori | Project diunggah ke GitHub (repo **public** `silakep-bkpsdm-empatlawang`). Ditambah `README.md` utama, `.gitignore` root, dan langkah instalasi setelah clone. `.env` dan database tidak ikut diunggah. | Claude |
| 2026-11-19 | Lingkungan | Server lokal mati setelah sesi di-restart. Dijalankan ulang dengan `php artisan serve --no-reload`. Disarankan selalu memakai `jalankan.bat`. | Claude |
| 2026-11-19 | Dokumen | `BRIEF.md` diberi catatan progres MVP KGB. `README.md` aplikasi berisi cara menjalankan, akun demo, skenario demo, dan daftar nilai perkiraan. | Claude |
| 2026-11-19 | SK PDF | Tata letak SK KGB dirapikan supaya muat **1 halaman A4** (sebelumnya blok tanda tangan dan tembusan terpotong ke halaman 2). | Claude |
| 2026-11-19 | Perbaikan | Bug: daftar Usulan KGB error untuk peran selain Super Admin (parameter closure Filament harus bernama `$query`). Sudah diperbaiki. | Claude |
| 2026-11-19 | Perbaikan | Bug: cetak SK massal error (`loadMissing`). Sudah diperbaiki. | Claude |
| 2026-11-19 | Testing | 21 test otomatis (132 assertion) **lulus semua**: hitung KGB, deteksi, alur persetujuan, hak akses, proses massal, impor Excel, halaman publik, PDF, dan login NIP. | Claude |
| 2026-11-19 | Fitur KGB | Halaman publik: beranda, lacak status (wajib nomor usulan + NIP), verifikasi keaslian SK lewat QR atau nomor SK (NIP disamarkan). | Claude |
| 2026-11-19 | Fitur KGB | Dashboard: statistik per peran, tabel usulan mendesak, rekap per OPD. Untuk ASN: KGB berikutnya, gaji saat ini, status usulan. | Claude |
| 2026-11-19 | Fitur KGB | Impor Excel data pegawai (upsert berdasarkan NIP) dan template Excel. Menu tabel gaji pokok, OPD, dan pengguna. | Claude |
| 2026-11-19 | Fitur KGB | SK KGB berupa PDF otomatis: nomor SK otomatis, QR verifikasi, data pegawai diperbarui saat SK terbit. Bisa cetak banyak SK dalam 1 PDF. | Claude |
| 2026-11-19 | Fitur KGB | Alur persetujuan: Admin OPD → Verifikator → Kabid → Kepala BKPSDM. Ada aksi kembalikan dan tolak (alasan wajib), proses massal per tahap, riwayat/tracking, dan notifikasi di aplikasi. | Claude |
| 2026-11-19 | Fitur KGB | Mesin KGB: deteksi otomatis jatuh tempo (≤ 3 bulan ke depan dan yang terlewat, PNS saja, tanpa duplikasi). Menghitung TMT, MKG, dan gaji baru, serta memberi peringatan untuk SKP, hukdis, dan MKG maksimal. Deteksi terjadwal harian pukul 01.00. | Claude |
| 2026-11-19 | Fondasi | Project Laravel 13 + Filament 5 + SQLite di folder `silakep/`. Login dengan NIP/username, 6 peran, data dummy (38 OPD, 400 pegawai, 43 akun). | Claude |
| 2026-11-19 | Lingkungan | Ekstensi PHP `intl`, `zip`, `gd`, `pdo_mysql`, `exif`, `sodium` diaktifkan (backup: `php.ini.bak-sebelum-silakep`). Composer dipasang di `%USERPROFILE%\tools`. | Claude |
| 2026-11-19 | Ruang lingkup | **Keputusan: MVP dibatasi ke modul KGB saja.** Info yang belum pasti diisi dummy/perkiraan. | Pemilik project |
| 2026-11-19 | Dokumen | `BRIEF.md` v0.2: prioritas disusun ulang sesuai keluhan (tubel, penyetaraan gelar, KGB, mutasi), ada perkiraan skala, keputusan dan asumsi. | Claude |
| 2026-11-19 | Dokumen | `BRIEF.md` v0.1 dibuat. | Claude |

---

## 3. Keputusan

| Tanggal | Keputusan | Dasar / Sumber |
|---|---|---|
| 2026-11-19 | Penandatangan SK KGB = **Kepala BKPSDM** | Pemilik project |
| 2026-11-19 | Pengembang produksi = **Diskominfo** | Pemilik project |
| 2026-11-19 | Sumber data pegawai: SIASN + Excel BKPSDM (asumsi). MVP memakai impor Excel. | Asumsi, perlu konfirmasi |
| 2026-11-19 | TTE BSrE masih diproses. Sementara memakai tanda tangan manual + QR verifikasi. | Pemilik project |
| 2026-11-19 | MVP hanya KGB, dan hanya untuk **PNS** (PPPK belum, karena tabel gajinya berbeda) | Pembatasan ruang lingkup MVP |
| 2026-11-19 | Teknologi MVP: Laravel 13, Filament 5, SQLite. Produksi: MySQL (menyesuaikan standar Diskominfo). | Usulan, perlu persetujuan Diskominfo |
| 2026-11-19 | Usulan KGB dibuat otomatis 3 bulan sebelum TMT | Perkiraan, bisa diubah di `config/silakep.php` |

---

## 4. Checklist Sebelum Produksi (Data Dummy/Perkiraan yang Harus Diganti)

- [ ] **Tabel gaji pokok** diganti dengan lampiran PP gaji resmi (saat ini nilai tengah hasil interpolasi)
- [ ] Nama, NIP, dan pangkat **Kepala BKPSDM** (`.env` → `SILAKEP_KEPALA_*`)
- [ ] **Alamat kantor** untuk kop surat (`.env` → `SILAKEP_ALAMAT`)
- [ ] **Format nomor SK** sesuai tata naskah dinas (`.env` → `SILAKEP_FORMAT_NOMOR_SK`)
- [ ] **Template SK KGB** disamakan dengan format resmi Empat Lawang (`resources/views/pdf/sk-kgb.blade.php`)
- [ ] **Format kolom Excel** disesuaikan dengan ekspor SIASN/BKPSDM yang asli
- [ ] **Daftar OPD** disesuaikan dengan Perda SOTK terbaru
- [ ] Data pegawai riil diimpor, lalu **TMT KGB terakhir diverifikasi oleh OPD**
- [ ] Akun Admin OPD riil dibuat dan password demo dihapus
- [ ] Database dipindah ke MySQL, queue memakai worker, scheduler dipasang (cron/Task Scheduler)
- [ ] Server, subdomain, dan HTTPS dari Diskominfo
- [ ] Integrasi TTE BSrE (`SILAKEP_TTE_AKTIF=true` + API)
- [ ] Uji coba di 2–3 OPD
- [ ] SK/SOP layanan online dari Kepala BKPSDM

---

## 5. Pertanyaan Terbuka

| No | Pertanyaan | Ditujukan ke | Status |
|---|---|---|---|
| 1 | Contoh 5–10 baris Excel pegawai (data disamarkan) | BKPSDM | ⏳ Belum |
| 2 | Contoh SK KGB asli yang dipakai sekarang | Bidang Mutasi/Kepegawaian | ⏳ Belum |
| 3 | Lampiran tabel gaji pokok PNS resmi yang berlaku | Bidang Mutasi / BPKAD | ⏳ Belum |
| 4 | Jumlah ASN (PNS/PPPK) dan OPD yang pasti | BKPSDM / SIASN | ⏳ Belum (perkiraan 4.000–7.000 ASN, 30–40 OPD) |
| 5 | Format nomor surat dan kode klasifikasi arsip untuk KGB | Bagian Umum / Tata Naskah | ⏳ Belum |
| 6 | Alur pencantuman gelar: bagian di daerah vs di SIASN | Bidang Diklat | ⏳ Belum |
| 7 | Penandatangan SK tubel dan perpanjangan tubel | Bidang Diklat | ⏳ Belum |
| 8 | Alur mutasi antar OPD saat ini | Bidang Mutasi | ⏳ Belum |
| 9 | Standar teknologi dan server Diskominfo | Diskominfo | ⏳ Belum |

**Sudah terjawab:** penandatangan SK KGB (Ka. BKPSDM), tim pengembang (Diskominfo), status TTE (sedang proses BSrE), layanan paling dikeluhkan (tubel, penyetaraan gelar, KGB, mutasi).

---

## 6. Rencana Berikutnya

| Urutan | Pekerjaan | Status |
|---|---|---|
| 1 | Demo MVP KGB ke Kepala BKPSDM | ⏳ |
| 2 | Kumpulkan bahan di Bagian 5 (Excel, contoh SK, tabel gaji) dan sesuaikan aplikasi | ⏳ |
| 3 | Presentasi dan serah terima ke Diskominfo | ⏳ |
| 4 | Modul tubel + perpanjangan (memakai pola alur yang sama dengan KGB) | ⏳ |
| 5 | Modul penyetaraan/pencantuman gelar | ⏳ |

---

## 7. Catatan Lingkungan Pengembangan

| Item | Nilai |
|---|---|
| Lokasi aplikasi | `silakep/` |
| Menjalankan | `silakep/jalankan.bat` → http://localhost:8000 |
| Reset data demo | `silakep/reset-data-demo.bat` |
| Akun demo | `admin`, `verifikator`, `kabid`, `kepala`, `opd.<kode>` (password: `password`) |
| Test otomatis | `php artisan test` (21 test) |
| PHP | 8.3 (WinGet), php.ini sudah diubah. Backup: `php.ini.bak-sebelum-silakep` |
| Composer | `%USERPROFILE%\tools\composer.phar` |
| Git / GitHub | https://github.com/sairu53871/silakep-bkpsdm-empatlawang (public), branch `main` |
