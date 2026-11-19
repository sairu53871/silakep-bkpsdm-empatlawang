@extends('publik.layout')
@section('judul', 'Lacak Usulan')
@section('isi')
@if (! $usulan)
    <div class="kartu">
        <h1>Usulan tidak ditemukan</h1>
        <p class="muted">Pastikan nomor usulan dan NIP sudah benar. Nomor usulan bisa dilihat di notifikasi aplikasi atau ditanyakan ke Admin OPD.</p>
        <a class="tombol" href="{{ route('beranda') }}">Kembali</a>
    </div>
@else
    @php
        $s = $usulan->status;
        $tahapSekarang = $s->tahap();
    @endphp
    <div class="kartu">
        <div class="muted">Usulan Kenaikan Gaji Berkala</div>
        <h1>{{ $usulan->nomor_usulan }}</h1>
        <table class="info">
            <tr><td>Nama</td><td><b>{{ $usulan->pegawai->nama_lengkap }}</b></td></tr>
            <tr><td>TMT KGB</td><td>{{ $usulan->tmt_baru->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Status saat ini</td><td><span class="badge {{ $s->getColor() }}">{{ $s->getLabel() }}</span></td></tr>
            @if ($usulan->catatan && in_array($s, [\App\Enums\StatusUsulan::Perbaikan, \App\Enums\StatusUsulan::Ditolak]))
                <tr><td>Keterangan</td><td>{{ $usulan->catatan }}</td></tr>
            @endif
            @if ($usulan->nomor_sk)
                <tr><td>Nomor SK</td><td>{{ $usulan->nomor_sk }}</td></tr>
            @endif
        </table>
    </div>

    <div class="kartu">
        <h2>Tahapan</h2>
        <ol class="langkah">
            @foreach ($tahapan as $no => $nama)
                @php
                    $kelas = $no < $tahapSekarang || $s === \App\Enums\StatusUsulan::Selesai ? 'selesai' : ($no === $tahapSekarang ? 'aktif' : '');
                    if ($no === $tahapSekarang && in_array($s, [\App\Enums\StatusUsulan::Perbaikan, \App\Enums\StatusUsulan::Ditolak])) $kelas = 'masalah';
                @endphp
                <li class="{{ $kelas }}"><span class="titik">{{ $kelas === 'selesai' ? '✓' : $no }}</span>{{ $nama }}</li>
            @endforeach
        </ol>
    </div>

    <div class="kartu">
        <h2>Riwayat</h2>
        <table class="info">
            @foreach ($usulan->riwayats as $r)
                <tr>
                    <td>{{ $r->created_at->translatedFormat('d M Y, H:i') }}</td>
                    <td><span class="badge {{ $r->status_ke->getColor() }}">{{ $r->status_ke->getLabel() }}</span>
                        @if ($r->catatan)<div class="muted">{{ $r->catatan }}</div>@endif</td>
                </tr>
            @endforeach
        </table>
    </div>
    <a class="tombol" href="{{ route('beranda') }}">Kembali</a>
@endif
@endsection
