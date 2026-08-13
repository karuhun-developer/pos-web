<!DOCTYPE html>
{{-- lang="id" tetap, bukan app()->getLocale(): seluruh copy UI web memang
     Bahasa Indonesia, sementara APP_LOCALE dipakai untuk pesan validasi API. --}}
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- SEO dirender di server, bukan lewat <Head> Inertia: tanpa SSR, perayap
         dan scraper pratinjau tautan tidak menjalankan JS sama sekali, jadi
         apa pun yang ditulis Vue tidak pernah mereka lihat. Atribut `inertia`
         di judul & deskripsi bikin dua tag ini diambil alih head manager
         Inertia begitu halaman berpindah di klien — nilainya diganti, bukan
         digandakan (nilai atributnya = head-key di GuestLayout.vue). --}}
    @php($seo = App\Support\PageSeo::for(request()))
    <title inertia>{{ $seo['title_full'] }}</title>
    <meta inertia="description" name="description" content="{{ $seo['description'] }}">
    <link rel="canonical" href="{{ $seo['url'] }}">
    @unless ($seo['index'])
        <meta name="robots" content="noindex, nofollow">
    @endunless

    {{-- Tag Open Graph sengaja TANPA atribut `inertia`: yang membacanya cuma
         scraper, dan scraper tidak pernah pindah halaman di klien. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ App\Support\PageSeo::BRAND }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $seo['title_full'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['url'] }}">
    <meta property="og:image" content="{{ url('/apple-touch-icon.png') }}">
    <meta name="twitter:card" content="summary">

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
