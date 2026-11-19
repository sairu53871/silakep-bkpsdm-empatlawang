# Brief Project: Sistem Layanan Kepegawaian Online BKPSDM Kabupaten Empat Lawang

> Nama kerja: **SILAKEP** (Sistem Layanan Kepegawaian Online). Bisa diganti.
> Versi brief: 0.2, 19 November 2026
> Status: Draft, belum disetujui pimpinan
> Pengembang: **Diskominfo Kabupaten Empat Lawang**
> **Progres:** MVP modul KGB (prototype, data dummy) sudah berjalan di folder [`silakep/`](silakep/README.md). Modul lain belum dikerjakan.

---

## 1. Latar Belakang

Layanan kepegawaian di BKPSDM Empat Lawang masih diajukan secara manual: berkas fisik dibawa dari OPD ke BKPSDM. Layanan yang **paling sering dikeluhkan** menurut masukan awal:

1. **Perpanjangan tugas belajar (tubel)**
2. **Penyetaraan / pencantuman gelar** setelah ASN lulus S1/S2 (Bidang Diklat/Pengembangan SDM)
3. **Kenaikan Gaji Berkala (KGB)**, yang sering terlambat
4. **Mutasi**

Masalah umum yang muncul:

- ASN tidak tahu posisi berkasnya dan harus bolak-balik bertanya.
- Berkas sering kurang lengkap, sehingga prosesnya berulang.
- Tenggat waktu (TMT KGB, masa berakhir tubel) sering terlewat karena tidak ada pengingat.
- Pimpinan tidak punya rekap jumlah pengajuan dan lama proses.
- Keaslian surat yang diterbitkan sulit dicek pihak lain.

## 2. Tujuan

1. ASN dan admin OPD bisa **mengajukan layanan secara online** dari mana saja.
2. Status pengajuan bisa **dilacak secara real-time**, seperti layanan perizinan online atau SIM online.
3. **Layanan yang punya tenggat diproses proaktif** (KGB dan masa tubel). Sistem mendeteksi dan mengingatkan sebelum jatuh tempo.
4. Surat atau SK **dibuat otomatis dari template**, lengkap dengan QR code untuk cek keaslian.
5. Pimpinan punya **dashboard** rekap layanan dan pemantauan SLA.

## 3. Prinsip dan Batasan

- **Tidak menduplikasi SIASN BKN.** Untuk layanan yang final di BKN (kenaikan pangkat, pensiun, mutasi antar instansi, dan pencantuman gelar yang diproses di SIASN), aplikasi ini hanya mengurus **pengajuan dan verifikasi berkas di tingkat daerah** sebelum diteruskan ke SIASN.
- **Patuh SPBE.** Dikembangkan oleh Diskominfo (Perpres 95/2018 tentang SPBE dan Perpres 82/2023 tentang percepatan transformasi digital).
- **Bukan pengganti SRIKANDI.** SRIKANDI tetap dipakai untuk surat-menyurat dinas. SILAKEP adalah aplikasi layanan.
- **TTE memakai BSrE BSSN.** Pengajuan kerja sama sedang berproses. Aplikasi dirancang **siap TTE** sejak awal.
- **Web responsif** yang nyaman dibuka di HP. Tidak membuat aplikasi Android/iOS.

## 4. Pengguna dan Peran

| Peran | Tugas |
|---|---|
| **ASN (pemohon)** | Mengajukan layanan, upload berkas, melacak status, mengunduh surat |
| **Admin OPD** | Memeriksa dan meneruskan pengajuan pegawai di OPD-nya, mengajukan atas nama pegawai, mengajukan KGB massal |
| **Verifikator BKPSDM** | Memeriksa kelengkapan dan keabsahan berkas, mengembalikan berkas untuk perbaikan |
| **Kepala Bidang** | Menyetujui atau menolak (paraf) |
| **Kepala BKPSDM** | **Penandatangan SK KGB** dan surat layanan BKPSDM lainnya |
| **Super Admin** | Mengelola master data, template surat, pengguna, dan tabel gaji |
| **Publik** | Mengecek keaslian surat lewat QR code atau nomor surat |

> Untuk surat yang kewenangannya di atas Kepala BKPSDM (misalnya SK mutasi oleh Bupati/PPK), alur penandatangan harus bisa diatur per jenis layanan.

## 5. Gambaran Skala (Perkiraan)

Data resmi terbaru belum ditemukan secara publik. Angka di bawah ini **perkiraan untuk perencanaan** dan harus dikonfirmasi ke data SIASN/BKPSDM.

| Item | Perkiraan | Dasar |
|---|---|---|
| Jumlah PNS | ± 3.000–4.500 | Tercatat 3.002 PNS pada 2011 (JPNN), ditambah rekrutmen sesudahnya, dikurangi pensiun |
| Jumlah PPPK | ± 1.000–2.500 | Rekrutmen PPPK 2021–2025 (contoh: 375 PPPK Guru lulus seleksi 2023), termasuk PPPK paruh waktu |
| **Total ASN** | **± 4.000–7.000** | Wajar untuk kabupaten berpenduduk ± 336 ribu jiwa (BPS 2024) |
| Perangkat daerah | ± 30–40 OPD | Setda, Setwan, Inspektorat, dinas, badan, RSUD, dan **10 kecamatan** |
| **SK KGB per bulan** | **± 150–200** | Jumlah PNS ÷ 24 bulan |

Dampak ke desain sistem:
- Skalanya kecil-menengah, jadi **cukup satu server** dengan Laravel + MySQL.
- KGB **wajib bisa diproses massal**, karena menandatangani ratusan SK satu per satu tidak realistis.
- Perlu sekitar **30–40 akun Admin OPD**. Sekolah dan puskesmas bisa ikut OPD induknya.

## 6. Daftar Layanan

Prioritas disusun ulang berdasarkan layanan yang paling sering dikeluhkan.

### Prioritas 1 (versi pertama)
| No | Layanan | Bidang | Catatan |
|---|---|---|---|
| 1 | **Kenaikan Gaji Berkala (KGB)** ⭐ | Mutasi/Kepegawaian | Otomatis dan massal, ditandatangani Ka. BKPSDM |
| 2 | **Tugas belajar & perpanjangan tubel** ⭐ | Diklat/Pengembangan SDM | Pengingat otomatis sebelum masa tubel berakhir |
| 3 | **Penyetaraan / pencantuman gelar** ⭐ | Diklat/Pengembangan SDM | Setelah lulus S1/S2. Verifikasi di daerah, lalu diteruskan ke SIASN jika diperlukan. |
| 4 | Izin belajar | Diklat/Pengembangan SDM | Satu alur dengan tubel |

### Prioritas 2
| No | Layanan | Catatan |
|---|---|---|
| 5 | **Mutasi dalam daerah (antar OPD)** | Sering dikeluhkan, tapi alurnya melibatkan pimpinan daerah. Perlu analisis lebih dalam. |
| 6 | Izin cuti | Volume tinggi |
| 7 | Surat keterangan | Tidak sedang dijatuhi hukuman disiplin, aktif bekerja |
| 8 | Usulan kenaikan pangkat | Verifikasi berkas, lalu diteruskan ke SIASN |
| 9 | Usulan pensiun | Verifikasi berkas, lalu diteruskan ke SIASN/Taspen |

### Prioritas 3
| No | Layanan |
|---|---|
| 10 | Rekomendasi izin perceraian (PP 10/1983 jo PP 45/1990) |
| 11 | Usulan Satyalancana Karya Satya |
| 12 | Usulan Karis/Karsu |
| 13 | Legalisir SK |

## 7. Fitur Utama

| Fitur | Keterangan |
|---|---|
| Login | NIP + password. Tahap berikutnya ditambah OTP lewat WhatsApp. |
| Master data pegawai | **Impor dari Excel BKPSDM** (bersumber dari SIASN). Integrasi API SIASN di tahap lanjut. |
| Form pengajuan dinamis | Syarat berkas diatur per jenis layanan |
| Upload berkas | PDF/JPG dengan batas ukuran |
| Alur persetujuan | Bisa diatur per layanan. Bawaan: Pemohon → Admin OPD → Verifikator → Kabid → Ka. BKPSDM. Bisa dikembalikan untuk perbaikan. |
| Tracking status | Riwayat per tahap, lengkap dengan waktu dan catatan |
| Lacak publik | Cek status pakai nomor pengajuan tanpa login |
| Pengingat tenggat | KGB jatuh tempo, masa tubel hampir habis |
| Notifikasi | Di dalam aplikasi. Tahap berikutnya lewat WhatsApp/email. |
| Surat otomatis | Template diisi otomatis lalu jadi PDF |
| QR code verifikasi | Halaman publik untuk cek keaslian surat |
| Siap TTE | Kolom dan alur tanda tangan dipisah supaya bisa diganti ke BSrE tanpa merombak sistem |
| Dashboard | Jumlah pengajuan per layanan/status/OPD, pengajuan lewat SLA, KGB jatuh tempo, tubel hampir berakhir |
| Log aktivitas | Mencatat siapa melakukan apa dan kapan |

## 8. Detail Modul Prioritas 1

### 8.1 Kenaikan Gaji Berkala (KGB)

**Aturan dasar**
- KGB diberikan **setiap 2 tahun** sejak TMT KGB terakhir.
- Syarat:
  - penilaian kinerja (SKP) minimal **Baik**
  - tidak sedang menjalani hukuman disiplin **penundaan KGB** (PP 94/2021)
- Gaji pokok baru mengikuti **tabel gaji PNS terbaru** (terakhir PP 5/2024). Cek ulang jika ada perubahan, dan tabel harus bisa diubah admin.
- Penandatangan: **Kepala BKPSDM**.

**Alur**
1. **Deteksi otomatis**: setiap bulan sistem mencari pegawai dengan TMT KGB berikutnya dalam 3 bulan ke depan.
2. **Notifikasi** ke ASN dan Admin OPD.
3. **Konfirmasi** oleh Admin OPD (bisa massal per OPD): SK KGB terakhir, SKP.
4. **Verifikasi BKPSDM**: cek SKP dan status hukuman disiplin.
5. **SK KGB dibuat otomatis**: gaji lama ke baru, MKG, TMT. Diproses **massal**.
6. **Tanda tangan Ka. BKPSDM**: sementara tanda tangan gambar/scan + QR, tahap berikutnya TTE BSrE (sekali proses untuk banyak SK).
7. **Distribusi SK** ke ASN, OPD, dan BPKAD (penyesuaian gaji).
8. **Data diperbarui**: TMT KGB baru menjadi patokan KGB berikutnya.

**Data**: NIP, nama, golongan, TMT KGB terakhir, MKG, gaji pokok terakhir, unit kerja, SKP, data hukuman disiplin, template SK KGB Empat Lawang.

> ⚠️ **Data TMT KGB terakhir dari Excel harus dibersihkan dan diverifikasi OPD** sebelum sistem dipakai.

### 8.2 Tugas Belajar & Perpanjangan Tubel

**Masalah saat ini**: masa tubel habis sebelum perpanjangan selesai diurus, dan ASN tidak tahu berkas apa saja yang dibutuhkan.

**Alur pengajuan tubel baru**
1. ASN mengajukan, dengan berkas: surat penerimaan kampus, rekomendasi atasan/OPD, info akreditasi prodi, dan berkas pendukung lain sesuai SOP.
2. Admin OPD → verifikator Bidang Diklat → Kabid → Ka. BKPSDM (atau pejabat sesuai kewenangan).
3. Surat/SK tubel terbit. **Tanggal mulai dan berakhir tubel dicatat** di sistem.

**Alur perpanjangan (proaktif)**
1. Sistem mengingatkan ASN dan Admin OPD **3 bulan sebelum masa tubel berakhir**.
2. ASN upload: surat keterangan masih kuliah dan perlu perpanjangan dari kampus, transkrip/laporan kemajuan studi, SK tubel sebelumnya.
3. Verifikasi, lalu persetujuan, lalu SK perpanjangan terbit. Tanggal berakhir diperbarui.
4. Dashboard menampilkan **daftar tubel yang hampir habis** dan **tubel yang lewat masa tanpa perpanjangan**.

### 8.3 Penyetaraan / Pencantuman Gelar

**Masalah saat ini**: setelah lulus S1/S2, ASN bingung syaratnya, prosesnya lama, dan statusnya tidak jelas.

**Alur**
1. ASN mengajukan, dengan berkas: ijazah dan transkrip (legalisir), **izin belajar/SK tubel sebelumnya**, bukti akreditasi prodi, dan bukti ijazah terdaftar di **PDDikti**.
2. Sistem **otomatis menghubungkan** pengajuan dengan data izin belajar/tubel ASN tersebut (jika pernah diajukan lewat SILAKEP).
3. Verifikasi Bidang Diklat: cek kesesuaian prodi dengan izin belajar, akreditasi, PDDikti.
4. Persetujuan, lalu surat/keterangan terbit dan/atau **diteruskan ke SIASN** (jika pencantuman gelar final di BKN).
5. Status dan riwayatnya bisa dilacak ASN.

> Alur pasti (bagian mana yang di daerah dan mana yang di SIASN) dikonfirmasi ke Bidang Diklat/Pengembangan SDM.

### 8.4 Izin Belajar
Memakai alur yang sama dengan tubel baru, dengan berkas yang berbeda. Data izin belajar menjadi dasar pencantuman gelar nantinya.

## 9. Di Luar Ruang Lingkup (Versi Pertama)

- Aplikasi mobile native (Android/iOS)
- Chatbot / AI
- Integrasi API SIASN (impor Excel dulu)
- Pembayaran (layanan ASN tidak berbayar)
- Pengganti SRIKANDI untuk surat-menyurat dinas
- Pembuatan sistem TTE sendiri
- Modul mutasi (masuk prioritas 2 setelah analisis alur)

## 10. Teknologi

> Karena dikembangkan oleh **Diskominfo**, pilihan teknologi di bawah ini adalah usulan. **Sesuaikan dengan standar dan kemampuan tim Diskominfo.**

| Komponen | Usulan |
|---|---|
| Backend | **Laravel** (PHP 8.3) |
| Panel admin | **Filament** |
| Database | SQLite (prototype), lalu **MySQL/MariaDB** (produksi) |
| Impor data | Laravel Excel (impor data pegawai dari Excel BKPSDM) |
| PDF | DomPDF / Browsershot |
| QR code | simple-qrcode |
| Hosting | Server/pusat data Pemkab Empat Lawang (Diskominfo) |
| Domain | Subdomain `*.empatlawangkab.go.id` |
| TTE (lanjutan) | API BSrE BSSN, setelah kerja sama disetujui |
| Notifikasi (lanjutan) | WhatsApp gateway / email |

## 11. Rencana Kerja

### Tahap A: Prototype (1 minggu)
Tujuan: demo yang bisa dipakai presentasi ke Kepala BKPSDM dan bahan diskusi dengan Diskominfo.

| Hari | Target |
|---|---|
| 1 | Setup Laravel + Filament, login, role, **impor Excel pegawai** (data dummy dengan format SIASN) |
| 2 | Modul KGB: hitung jatuh tempo, tabel gaji pokok, daftar KGB jatuh tempo, proses massal |
| 3 | Alur persetujuan (bisa diatur per layanan), tracking status, riwayat, upload berkas |
| 4 | SK KGB berupa PDF otomatis, QR code, halaman verifikasi publik |
| 5 | **Modul tubel + perpanjangan** (pengingat masa berakhir) dan **izin belajar** |
| 6 | **Modul penyetaraan/pencantuman gelar**, halaman lacak status publik, dashboard |
| 7 | Testing, tampilan rapi di HP, data demo |

Belum termasuk: TTE BSrE, WhatsApp, integrasi SIASN, server online (masih berjalan di laptop).

### Tahap B: Menuju produksi (± 3–4 bulan setelah prototype disetujui)
| Kegiatan | Durasi |
|---|---|
| Serah terima prototype + brief ke tim Diskominfo | 1 minggu |
| Analisis bersama Bidang Mutasi & Bidang Diklat (SOP, syarat, format surat) | 2–3 minggu |
| Penyempurnaan layanan prioritas 1 + **migrasi data riil dari Excel/SIASN** | 4–6 minggu |
| Integrasi TTE BSrE (jika kerja sama sudah disetujui) | Paralel |
| Uji coba di 2–3 OPD | 3–4 minggu |
| Peluncuran, sosialisasi, SK Kaban/SOP | 2 minggu |

### Tahap C: Pengembangan lanjutan (+2–3 bulan)
- Modul mutasi dalam daerah
- Layanan prioritas 2 dan 3
- Notifikasi WhatsApp
- Integrasi API SIASN (jika akses diberikan)

## 12. Kebutuhan Koordinasi

| Pihak | Kebutuhan |
|---|---|
| Kepala BKPSDM | Persetujuan project, penunjukan tim, SK/SOP layanan online, spesimen tanda tangan |
| Bidang Mutasi/Kepegawaian | SOP KGB, template SK KGB, data TMT KGB |
| Bidang Diklat/Pengembangan SDM | SOP tubel, perpanjangan, izin belajar, penyetaraan gelar, beserta format suratnya |
| **Diskominfo** | **Pengembangan aplikasi**, hosting, subdomain, keamanan, kepatuhan SPBE |
| BSSN (via Diskominfo) | Penyelesaian kerja sama BSrE, sertifikat elektronik Ka. BKPSDM |
| BPKAD | Alur penyampaian SK KGB untuk penyesuaian gaji |
| Seluruh OPD | Penunjukan admin OPD, verifikasi data pegawai |

## 13. Risiko

| Risiko | Mitigasi |
|---|---|
| Data Excel pegawai (TMT KGB, golongan, tubel) tidak akurat | Verifikasi oleh OPD sebelum peluncuran |
| Alur dan format surat lama disepakati | Prototype dipakai sebagai alat diskusi, keputusan dipercepat |
| Tumpang tindih dengan SIASN/aplikasi pusat | Batasi ruang lingkup ke kewenangan daerah |
| Kerja sama BSrE belum selesai | Sementara pakai tanda tangan gambar + QR verifikasi, sistem sudah siap TTE |
| Kapasitas tim Diskominfo terbatas | Brief dan prototype yang jelas, ruang lingkup bertahap |
| ASN/OPD enggan beralih | Sosialisasi, tetap ada helpdesk, prosesnya dibuat lebih cepat dari cara manual |
| Keamanan data pribadi ASN (UU PDP 27/2022) | Hak akses per peran, HTTPS, backup, log aktivitas |

## 14. Keputusan dan Asumsi

| No | Pertanyaan | Jawaban / Asumsi |
|---|---|---|
| 1 | Penandatangan SK KGB | ✅ **Kepala BKPSDM** |
| 2 | Sumber data pegawai | 🟡 Asumsi: **SIASN**, dan BKPSDM punya **ekspor Excel** yang dipakai untuk impor awal |
| 3 | Jumlah ASN & OPD | 🟡 Perkiraan ± 4.000–7.000 ASN, ± 30–40 OPD (lihat Bagian 5). Perlu konfirmasi. |
| 4 | Kesiapan TTE | 🟡 Pengajuan persetujuan ke BSrE **sedang berproses** |
| 5 | Tim pengembang | ✅ **Diskominfo** |
| 6 | Layanan paling dikeluhkan | ✅ Perpanjangan tubel, penyetaraan/pencantuman gelar, KGB, mutasi |

### Pertanyaan yang masih terbuka
1. Format kolom Excel pegawai dari BKPSDM (minta contoh 5–10 baris yang sudah disamarkan).
2. Alur pencantuman gelar: bagian mana yang selesai di daerah, dan mana yang harus diteruskan ke SIASN?
3. Siapa penandatangan SK tubel dan perpanjangan tubel?
4. Bagaimana alur mutasi antar OPD saat ini, dan siapa saja yang terlibat?
5. Stack dan standar teknologi yang biasa dipakai tim Diskominfo.

## 15. Kriteria Berhasil

- 100% SK KGB terbit **sebelum TMT** (tidak ada keterlambatan).
- **Tidak ada tubel yang lewat masa** tanpa proses perpanjangan.
- Waktu proses penyetaraan gelar dan layanan lainnya turun menjadi **≤ 3–5 hari kerja**.
- ASN bisa melacak status **tanpa datang** ke BKPSDM.
- Semua surat terbit memiliki **QR code verifikasi**.
- Pimpinan bisa melihat rekap layanan **real-time** di dashboard.

## Referensi

- [JPNN: Kabupaten Empat Lawang Butuh 2.180 PNS (2011)](https://www.jpnn.com/news/kabupaten-empat-lawang-butuh-2180-pns)
- [Pengumuman Hasil Seleksi PPPK Guru Empat Lawang 2023](https://empatlawangkab.go.id/v2/wp-content/uploads/2023/12/Pengumuman-Hasil-Seleksi-PPPK-Guru.pdf)
- [Databoks: Penduduk Kabupaten Empat Lawang 2024](https://databoks.katadata.co.id/en/demographics/statistics/be84c683e1f8caf/2024-update-empat-lawang-regency-population-336160)
- [Wikipedia: Empat Lawang Regency](https://en.wikipedia.org/wiki/Empat_Lawang_Regency)
- [BPS Kabupaten Empat Lawang](https://empatlawangkab.bps.go.id)
