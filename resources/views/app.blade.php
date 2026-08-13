<!DOCTYPE html>
{{-- lang="id" tetap, bukan app()->getLocale(): seluruh copy UI web memang
     Bahasa Indonesia, sementara APP_LOCALE dipakai untuk pesan validasi API. --}}
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'POS Pro') }}</title>

    {{-- favicon.svg jadi yang utama (tajam di semua ukuran, ikut zoom tab);
         .ico tetap ada untuk browser lama yang mengabaikan tipe SVG. --}}
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#222933">

    {{-- Cat tema sebelum CSS dieksekusi supaya tidak ada kedipan terang→gelap. --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('pos-pro-theme');
                var dark = saved ? saved === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="min-h-screen bg-surface font-sans text-ink">
    @inertia
</body>
</html>
