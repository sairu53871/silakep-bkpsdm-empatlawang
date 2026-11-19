@extends('publik.layout')
@section('judul', 'Beranda')
@section('isi')
<div class="kartu">
    <h1>Layanan Kenaikan Gaji Berkala (KGB) Online</h1>
    <p class="muted" style="margin:0">Sistem otomatis mendeteksi KGB yang jatuh tempo, OPD mengajukan secara online, dan Bapak/Ibu bisa memantau prosesnya sampai SK terbit, tanpa perlu datang ke kantor.</p>
</div>

<div class="grid">
    <form class="kartu" method="GET" action="{{ route('lacak') }}">
        <h2>🔎 Lacak Status Usulan</h2>
        <p class="muted" style="margin:0">Masukkan nomor usulan dan NIP Anda.</p>
        <label for="nomor">Nomor Usulan</label>
        <input id="nomor" name="nomor" placeholder="contoh: KGB-202612-00017" value="{{ old('nomor', request('nomor')) }}" required>
        @error('nomor')<div class="galat">{{ $message }}</div>@enderror
        <label for="nip">NIP</label>
        <input id="nip" name="nip" inputmode="numeric" maxlength="18" placeholder="18 digit NIP" value="{{ old('nip', request('nip')) }}" required>
        @error('nip')<div class="galat">{{ $message }}</div>@enderror
        <button type="submit">Lacak</button>
    </form>

    <form class="kartu" method="GET" action="{{ route('verifikasi.cari') }}">
        <h2>✅ Cek Keaslian SK</h2>
        <p class="muted" style="margin:0">Pindai QR code pada SK, atau masukkan nomor SK.</p>
        <label for="nomor_sk">Nomor SK</label>
        <input id="nomor_sk" name="nomor_sk" placeholder="contoh: 800.1.11.1/0001/KGB/BKPSDM/2026" value="{{ old('nomor_sk') }}" required>
        @error('nomor_sk')<div class="galat">{{ $message }}</div>@enderror
        <button type="submit">Cek SK</button>
    </form>
</div>

<div class="kartu">
    <h2>Alur Layanan KGB</h2>
    <ol class="langkah">
        <li class="selesai"><span class="titik">1</span><b>Deteksi otomatis</b><div class="muted">Sistem mencari pegawai yang KGB-nya jatuh tempo 3 bulan ke depan dan mengirim pengingat.</div></li>
        <li class="selesai"><span class="titik">2</span><b>Pengajuan OPD</b><div class="muted">Admin OPD memeriksa data &amp; mengunggah berkas (SK terakhir, SKP).</div></li>
        <li class="selesai"><span class="titik">3</span><b>Verifikasi &amp; persetujuan BKPSDM</b><div class="muted">Verifikator memeriksa syarat (SKP minimal Baik, tidak sedang hukdis), lalu disetujui Kepala Bidang.</div></li>
        <li class="selesai"><span class="titik">4</span><b>Tanda tangan Kepala BKPSDM</b><div class="muted">SK terbit dengan QR code verifikasi dan dikirim ke pegawai, OPD, serta BPKAD.</div></li>
    </ol>
</div>
@endsection
