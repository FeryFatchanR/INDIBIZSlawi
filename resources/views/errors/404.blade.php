<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Halaman tidak ditemukan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-surface px-4 text-center">
    <div>
        <p class="font-display text-7xl font-extrabold text-secondary">404</p>
        <h1 class="mt-4 text-2xl font-bold">Halaman tidak ditemukan</h1>
        <p class="mt-2 text-slate-600">Halaman yang Anda cari mungkin sudah dipindahkan atau belum dipublikasikan.</p>
        <a href="{{ url('/') }}" class="mt-8 inline-flex rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white">Kembali ke beranda</a>
    </div>
</body>
</html>
