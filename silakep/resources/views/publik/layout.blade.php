<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('judul', 'SILAKEP') · BKPSDM Empat Lawang</title>
<style>
    :root { --biru:#1d4ed8; --biru-tua:#1e3a8a; --abu:#64748b; --garis:#e2e8f0; --latar:#f1f5f9; --hijau:#15803d; --merah:#b91c1c; --kuning:#b45309; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--latar); color:#0f172a; line-height:1.5; }
    header { background: linear-gradient(135deg, var(--biru-tua), var(--biru)); color:#fff; padding: 22px 16px 26px; }
    header .wrap { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    header a.merek { color:#fff; text-decoration:none; }
    header .nama { font-size: 1.35rem; font-weight: 700; letter-spacing:.5px; }
    header .sub { font-size: .85rem; opacity: .85; }
    .wrap { max-width: 880px; margin: 0 auto; }
    main { padding: 0 16px 40px; }
    .kartu { background:#fff; border:1px solid var(--garis); border-radius: 14px; padding: 20px; margin-top: 18px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    h1 { font-size: 1.25rem; margin: 0 0 6px; }
    h2 { font-size: 1.05rem; margin: 0 0 10px; }
    p.muted, .muted { color: var(--abu); font-size: .9rem; }
    label { display:block; font-size:.85rem; font-weight:600; margin: 10px 0 4px; }
    input { width:100%; padding: 11px 12px; border:1px solid #cbd5e1; border-radius: 9px; font-size: 1rem; }
    input:focus { outline: 2px solid #93c5fd; border-color: var(--biru); }
    button, .tombol { display:inline-block; margin-top: 14px; background: var(--biru); color:#fff; border:0; border-radius: 9px; padding: 11px 18px; font-size: .95rem; font-weight:600; cursor:pointer; text-decoration:none; }
    .tombol.putih { background:#fff; color: var(--biru-tua); }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    @media (max-width: 680px) { .grid { grid-template-columns: 1fr; } }
    .galat { color: var(--merah); font-size:.85rem; margin-top:4px; }
    .badge { display:inline-block; padding: 3px 10px; border-radius: 999px; font-size:.8rem; font-weight:600; }
    .badge.success { background:#dcfce7; color: var(--hijau); } .badge.danger { background:#fee2e2; color: var(--merah); }
    .badge.warning { background:#fef3c7; color: var(--kuning); } .badge.info { background:#dbeafe; color: var(--biru-tua); } .badge.gray { background:#e2e8f0; color:#334155; }
    table.info { width:100%; border-collapse: collapse; font-size:.95rem; }
    table.info td { padding: 7px 4px; border-bottom: 1px solid var(--garis); vertical-align: top; }
    table.info td:first-child { color: var(--abu); width: 42%; }
    .langkah { list-style:none; padding:0; margin: 8px 0 0; }
    .langkah li { position:relative; padding: 0 0 18px 34px; }
    .langkah li::before { content:""; position:absolute; left:9px; top:22px; bottom:0; width:2px; background: var(--garis); }
    .langkah li:last-child::before { display:none; }
    .langkah .titik { position:absolute; left:0; top:2px; width:20px; height:20px; border-radius:50%; background:#cbd5e1; color:#fff; font-size:12px; display:flex; align-items:center; justify-content:center; }
    .langkah li.selesai .titik { background: var(--hijau); } .langkah li.aktif .titik { background: var(--biru); box-shadow: 0 0 0 4px #bfdbfe; }
    .langkah li.masalah .titik { background: var(--kuning); }
    .status-besar { font-size: 1.15rem; font-weight: 700; }
    footer { text-align:center; color: var(--abu); font-size:.8rem; padding: 10px 16px 30px; }
</style>
</head>
<body>
<header>
    <div class="wrap">
        <a class="merek" href="{{ route('beranda') }}">
            <div class="nama">SILAKEP</div>
            <div class="sub">Sistem Layanan Kepegawaian Online · BKPSDM Kabupaten Empat Lawang</div>
        </a>
        <a class="tombol putih" style="margin:0" href="/admin">Masuk</a>
    </div>
</header>
<main><div class="wrap">@yield('isi')</div></main>
<footer>© {{ date('Y') }} BKPSDM Kabupaten Empat Lawang · Versi MVP (data contoh/dummy)</footer>
</body>
</html>
