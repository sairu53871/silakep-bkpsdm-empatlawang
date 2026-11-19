@extends('publik.layout')
@section('judul', 'Verifikasi SK')
@section('isi')
@if (! $usulan)
    <div class="kartu" style="border-color:#fecaca">
        <h1 style="color:#b91c1c">✖ SK tidak terdaftar</h1>
        <p class="muted">Nomor/kode SK tidak ditemukan di sistem BKPSDM Kabupaten Empat Lawang. Dokumen ini <b>tidak dapat dipastikan keasliannya</b>. Hubungi BKPSDM untuk konfirmasi.</p>
        <a class="tombol" href="{{ route('beranda') }}">Kembali</a>
    </div>
@else
    @php $nip = $usulan->pegawai->nip; @endphp
    <div class="kartu" style="border-color:#bbf7d0">
        <h1 style="color:#15803d">✔ SK terdaftar dan sah</h1>
        <p class="muted" style="margin-top:0">Surat Pemberitahuan Kenaikan Gaji Berkala ini diterbitkan oleh BKPSDM Kabupaten Empat Lawang. Cocokkan isi di bawah dengan dokumen yang Anda pegang.</p>
        <table class="info">
            <tr><td>Nomor SK</td><td><b>{{ $usulan->nomor_sk }}</b></td></tr>
            <tr><td>Tanggal SK</td><td>{{ $usulan->tanggal_sk->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Nama</td><td>{{ $usulan->pegawai->nama_lengkap }}</td></tr>
            <tr><td>NIP</td><td>{{ substr($nip, 0, 8) }}••••••{{ substr($nip, -4) }}</td></tr>
            <tr><td>Pangkat / Gol.</td><td>{{ $usulan->pangkat() }} ({{ $usulan->golongan }})</td></tr>
            <tr><td>Unit Kerja</td><td>{{ $usulan->opd->nama }}</td></tr>
            <tr><td>Gaji Pokok Baru</td><td>{{ \App\Support\Golongan::rupiah($usulan->gaji_baru) }}</td></tr>
            <tr><td>Terhitung Mulai</td><td>{{ $usulan->tmt_baru->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Penandatangan</td><td>{{ $usulan->penandatangan_nama }}<div class="muted">{{ $usulan->penandatangan_jabatan }}</div></td></tr>
        </table>
    </div>
    <a class="tombol" href="{{ route('beranda') }}">Kembali</a>
@endif
@endsection
