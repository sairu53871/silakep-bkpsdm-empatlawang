<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi SILAKEP
|--------------------------------------------------------------------------
| Nilai bertanda (DUMMY) / (PERKIRAAN) harus diganti dengan data resmi
| sebelum dipakai produksi. Semua bisa diatur lewat file .env.
*/

return [

    'instansi' => [
        'pemda' => 'PEMERINTAH KABUPATEN EMPAT LAWANG',
        'nama' => 'BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA',
        'alamat' => env('SILAKEP_ALAMAT', 'Jl. Contoh Alamat Kantor BKPSDM, Tebing Tinggi, Empat Lawang (DUMMY)'),
        'kota' => 'Tebing Tinggi',
    ],

    // Penandatangan SK KGB = Kepala BKPSDM (keputusan brief v0.2)
    'penandatangan' => [
        'nama' => env('SILAKEP_KEPALA_NAMA', 'NAMA KEPALA BKPSDM, S.STP., M.Si (DUMMY)'),
        'nip' => env('SILAKEP_KEPALA_NIP', '197501012000031001'),
        'pangkat' => env('SILAKEP_KEPALA_PANGKAT', 'Pembina Utama Muda (IV/c)'),
        'jabatan' => 'Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
    ],

    // Format nomor SK. {urut} dan {tahun} diganti otomatis. (PERKIRAAN — sesuaikan tata naskah dinas)
    'format_nomor_sk' => env('SILAKEP_FORMAT_NOMOR_SK', '800.1.11.1/{urut}/KGB/BKPSDM/{tahun}'),

    'kgb' => [
        // Usulan dibuat otomatis untuk pegawai yang TMT KGB berikutnya <= sekian bulan ke depan
        'deteksi_bulan_ke_depan' => 3,
        // Nilai SKP yang memenuhi syarat KGB
        'skp_memenuhi' => ['Sangat Baik', 'Baik'],
        'dasar_hukum_gaji' => 'PP Nomor 5 Tahun 2024',
    ],

    'nilai_skp' => ['Sangat Baik', 'Baik', 'Butuh Perbaikan', 'Kurang', 'Sangat Kurang'],

    // Status TTE: false = masih tanda tangan gambar/manual + QR. Ubah ke true setelah integrasi BSrE.
    'tte_aktif' => env('SILAKEP_TTE_AKTIF', false),
];
