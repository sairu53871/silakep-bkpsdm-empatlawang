@php use App\Support\Golongan; @endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>SK Kenaikan Gaji Berkala</title>
<style>
    @page { margin: 1.2cm 2cm 1.0cm 2.2cm; }
    body { font-family: "Times New Roman", serif; font-size: 11pt; line-height: 1.25; color: #000; }
    .halaman { page-break-after: always; }
    .halaman:last-child { page-break-after: auto; }
    .kop { width: 100%; border-bottom: 3px double #000; padding-bottom: 6px; margin-bottom: 14px; text-align: center; }
    .kop .pemda { font-size: 13pt; font-weight: bold; }
    .kop .instansi { font-size: 12.5pt; font-weight: bold; }
    .kop .alamat { font-size: 9.5pt; }
    table { border-collapse: collapse; }
    .meta td { padding: 0 4px 0 0; vertical-align: top; }
    .data td { padding: 1px 4px; vertical-align: top; }
    .data td.no { width: 22px; }
    .data td.lbl { width: 210px; }
    .data td.sep { width: 10px; }
    .tebal { font-weight: bold; }
    .ttd { width: 100%; margin-top: 8px; }
    .ttd td { vertical-align: top; }
    .kotak-tte { border: 1px solid #555; padding: 6px; font-size: 8.5pt; width: 245px; }
    .tembusan { font-size: 9.5pt; margin-top: 8px; } p { margin: 7px 0; }
    .catatan-kaki { margin-top: 10px; font-size: 7.5pt; color: #444; border-top: 1px solid #999; padding-top: 3px; }
</style>
</head>
<body>
@foreach ($usulans as $u)
@php $p = $u->pegawai; @endphp
<div class="halaman">
    <div class="kop">
        <div class="pemda">{{ $instansi['pemda'] }}</div>
        <div class="instansi">{{ $instansi['nama'] }}</div>
        <div class="alamat">{{ $instansi['alamat'] }}</div>
    </div>

    <table class="meta" style="width:100%">
        <tr>
            <td style="width:60%">
                <table class="meta">
                    <tr><td>Nomor</td><td>:</td><td>{{ $u->nomor_sk }}</td></tr>
                    <tr><td>Lampiran</td><td>:</td><td>-</td></tr>
                    <tr><td>Perihal</td><td>:</td><td class="tebal">Kenaikan Gaji Berkala</td></tr>
                </table>
            </td>
            <td style="text-align:right">{{ $instansi['kota'] }}, {{ $u->tanggal_sk->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <p style="margin:12px 0 4px 0">Kepada Yth.<br>
    Kepala Badan Pengelolaan Keuangan dan Aset Daerah<br>
    Kabupaten Empat Lawang<br>di&nbsp;&nbsp;&nbsp;Tebing Tinggi</p>

    <p style="text-align:justify">Dengan ini diberitahukan bahwa berhubung telah dipenuhinya masa kerja dan syarat-syarat lainnya kepada:</p>

    <table class="data">
        <tr><td class="no">1.</td><td class="lbl">Nama</td><td class="sep">:</td><td class="tebal">{{ $p->nama_lengkap }}</td></tr>
        <tr><td class="no">2.</td><td class="lbl">Tempat / Tanggal Lahir</td><td class="sep">:</td><td>{{ $p->tempat_lahir }}, {{ $p->tanggal_lahir?->translatedFormat('d F Y') }}</td></tr>
        <tr><td class="no">3.</td><td class="lbl">NIP</td><td class="sep">:</td><td>{{ $p->nip }}</td></tr>
        <tr><td class="no">4.</td><td class="lbl">Pangkat / Golongan Ruang</td><td class="sep">:</td><td>{{ $u->pangkat() }} ({{ $u->golongan }})</td></tr>
        <tr><td class="no">5.</td><td class="lbl">Jabatan</td><td class="sep">:</td><td>{{ $p->jabatan }}</td></tr>
        <tr><td class="no">6.</td><td class="lbl">Unit Kerja</td><td class="sep">:</td><td>{{ $u->opd->nama }}</td></tr>
        <tr><td class="no">7.</td><td class="lbl">Gaji Pokok Lama</td><td class="sep">:</td><td>{{ Golongan::rupiah($u->gaji_lama) }}</td></tr>
    </table>

    <p style="margin:6px 0 2px 32px">(atas dasar SK/KGB terakhir tentang gaji/pangkat yang ditetapkan)</p>
    <table class="data" style="margin-left:22px">
        <tr><td class="lbl">a. Tanggal mulai berlakunya</td><td class="sep">:</td><td>{{ $u->tmt_lama->translatedFormat('d F Y') }}</td></tr>
        <tr><td class="lbl">b. Masa kerja golongan</td><td class="sep">:</td><td>{{ Golongan::mkg($u->mkg_lama_tahun, $u->mkg_lama_bulan) }}</td></tr>
    </table>

    <p style="margin:8px 0 4px 0">diberikan kenaikan gaji berkala hingga memperoleh:</p>
    <table class="data">
        <tr><td class="no">8.</td><td class="lbl">Gaji Pokok Baru</td><td class="sep">:</td><td class="tebal">{{ Golongan::rupiah($u->gaji_baru) }}</td></tr>
        <tr><td class="no">9.</td><td class="lbl">Berdasarkan Masa Kerja</td><td class="sep">:</td><td>{{ Golongan::mkg($u->mkg_baru_tahun, $u->mkg_baru_bulan) }}</td></tr>
        <tr><td class="no">10.</td><td class="lbl">Dalam Golongan Ruang</td><td class="sep">:</td><td>{{ $u->golongan }}</td></tr>
        <tr><td class="no">11.</td><td class="lbl">Mulai Tanggal</td><td class="sep">:</td><td class="tebal">{{ $u->tmt_baru->translatedFormat('d F Y') }}</td></tr>
    </table>

    <p style="text-align:justify">Diharapkan agar sesuai dengan {{ config('silakep.kgb.dasar_hukum_gaji') }} kepada pegawai tersebut dapat dibayarkan penghasilannya berdasarkan gaji pokok yang baru.</p>

    <table class="ttd">
        <tr>
            <td style="width:45%">
                <img src="{{ $qr[$u->id] }}" style="width:84px;height:84px"><br>
                <span style="font-size:8pt">Pindai untuk cek keaslian<br>No. Usulan: {{ $u->nomor_usulan }}</span>
            </td>
            <td>
                {{ $u->penandatangan_jabatan }}<br>Kabupaten Empat Lawang,
                @if ($tteAktif)
                    <div class="kotak-tte" style="margin:6px 0">Ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan BSrE-BSSN</div>
                @else
                    <div style="height:52px; font-size:8.5pt; color:#777; padding-top:16px">(tanda tangan &amp; cap)</div>
                @endif
                <span class="tebal" style="text-decoration:underline">{{ $u->penandatangan_nama }}</span><br>
                {{ $u->penandatangan_pangkat }}<br>
                NIP. {{ $u->penandatangan_nip }}
            </td>
        </tr>
    </table>

    <div class="tembusan">
        Tembusan:<br>
        1. Bupati Empat Lawang (sebagai laporan);<br>
        2. Kepala {{ $u->opd->nama }};<br>
        3. Pegawai yang bersangkutan;<br>
        4. Arsip.
    </div>

    <div class="catatan-kaki">
        Dokumen diterbitkan melalui SILAKEP BKPSDM Kabupaten Empat Lawang. Keaslian dapat dicek di {{ route('verifikasi.sk', $u->token_verifikasi) }}
        @unless ($tteAktif) · TTE BSrE belum aktif (versi MVP). @endunless
    </div>
</div>
@endforeach
</body>
</html>
