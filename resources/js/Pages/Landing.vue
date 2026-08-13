<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  BadgeCheck,
  BarChart3,
  Download,
  FileSpreadsheet,
  Heart,
  Monitor,
  RefreshCw,
  ShieldCheck,
  Smartphone,
  WifiOff,
} from '@lucide/vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import GithubIcon from '@/Components/GithubIcon.vue'
import Button from '@/Components/ui/Button.vue'
import type { SharedProps } from '@/types'

const props = defineProps<{
  release: {
    version: string | null
    url: string
    apk: string | null
    size: number | null
    published_at: string | null
  }
  repos: { android: string; web: string }
}>()

const page = usePage<SharedProps>()
const loggedIn = computed(() => page.props.auth.user !== null)

/*
 * Kalau GitHub tidak bisa dihubungi, `apk` kosong — tombolnya jatuh ke halaman
 * rilis terbaru. Yang penting tombolnya tidak pernah mati.
 */
const downloadUrl = computed(() => props.release.apk ?? props.release.url)
const downloadLabel = computed(() =>
  props.release.version ? `Unduh APK ${props.release.version}` : 'Unduh aplikasi Android',
)

const megabytes = computed(() =>
  props.release.size ? `${(props.release.size / 1024 / 1024).toFixed(0)} MB` : null,
)

const releasedAt = computed(() => {
  if (!props.release.published_at) return null

  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(
    new Date(props.release.published_at),
  )
})

const FEATURES = [
  {
    icon: WifiOff,
    title: 'Jalan tanpa internet',
    body: 'Transaksi tetap tercatat waktu sinyal hilang. Datanya nunggu di HP, bukan hilang.',
  },
  {
    icon: RefreshCw,
    title: 'Sinkron sendiri',
    body: 'Begitu online, penjualan naik dan perubahan dari web turun. Tanpa tombol ekspor-impor.',
  },
  {
    icon: BarChart3,
    title: 'Laporan yang enak dibaca',
    body: 'Tren omzet, jam ramai, produk terlaris, margin per kategori — lengkap dengan tabelnya.',
  },
  {
    icon: FileSpreadsheet,
    title: 'Impor & ekspor massal',
    body: 'Unggah ribuan produk dari CSV/XLSX, dengan pratinjau per baris sebelum diterapkan.',
  },
  {
    icon: ShieldCheck,
    title: 'Akses per peran',
    body: 'Pemilik dan kasir punya hak berbeda. Data toko lain tidak pernah bisa disentuh.',
  },
  {
    icon: BadgeCheck,
    title: '100% gratis',
    body: 'Semua fiturnya kebuka buat semua orang. Tidak ada yang dikunci di balik bayaran.',
  },
]

/*
 * Repo digandeng ke aplikasinya masing-masing, bukan dibikin seksi sendiri:
 * daftar repo terpisah memaksa orang mencocokkan sendiri mana repo punya mana
 * aplikasi, padahal keterangannya sudah ada di kartu ini.
 */
const APPS = [
  {
    icon: Smartphone,
    title: 'POS Kacaw · Android',
    body: 'Kasir, keranjang, scan barcode, cetak struk, sesi kasir, dan arus kas. Semua tersimpan di HP dan tetap bisa dipakai offline.',
    stack: 'Vue + Capacitor · SQLite di HP',
    url: props.repos.android,
    repo: 'karuhun-developer/pos-android',
  },
  {
    icon: Monitor,
    title: 'POS Pro · panel web',
    body: 'Backend sinkronisasinya, sekaligus tempat kelola produk, undang kasir, baca laporan mendalam, dan impor/ekspor massal.',
    stack: 'Laravel · Inertia + Vue',
    url: props.repos.web,
    repo: 'karuhun-developer/pos-web',
  },
]
</script>

<template>
  <GuestLayout>
    <template #actions>
      <Link v-if="loggedIn" :href="route('dashboard')">
        <Button size="sm">Dashboard</Button>
      </Link>
      <template v-else>
        <Link :href="route('login')">
          <Button variant="ghost" size="sm">Masuk</Button>
        </Link>
        <Link :href="route('register')">
          <Button size="sm">Daftar</Button>
        </Link>
      </template>
    </template>

    <!-- Hero -->
    <section class="mx-auto w-full max-w-5xl px-4 pt-16 pb-14 text-center sm:px-6 sm:pt-24 sm:pb-20">
      <span
        class="inline-flex items-center gap-2 rounded-full border border-border px-3 py-1
               font-mono text-[11px] tracking-widest text-ink-muted uppercase"
      >
        Gratis 100% · Open source
      </span>

      <h1
        class="mx-auto mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-balance text-ink sm:text-6xl"
      >
        Kasir yang jalan terus, walau sinyal hilang.
      </h1>

      <p class="mx-auto mt-5 max-w-xl text-base text-pretty text-ink-muted">
        POS Kacaw mencatat penjualan langsung di HP Android — tanpa internet. Begitu ada sinyal,
        datanya naik sendiri ke panel web: kelola produk, atur hak akses kasir, dan baca laporan
        di layar besar.
      </p>

      <div class="mt-8 flex flex-wrap items-center justify-center gap-2.5">
        <Link :href="route('donate.index')">
          <Button>
            <Heart class="size-4" />
            Dukung
          </Button>
        </Link>
        <a :href="downloadUrl" target="_blank" rel="noopener noreferrer">
          <Button>
            <Download class="size-4" />
            {{ downloadLabel }}
          </Button>
        </a>
        <Link :href="loggedIn ? route('dashboard') : route('register')">
          <Button variant="outline">
            {{ loggedIn ? 'Buka panel web' : 'Bikin akun panel web' }}
          </Button>
        </Link>
      </div>

      <p class="mt-4 font-mono text-xs text-ink-subtle">
        Android
        <template v-if="megabytes"> · {{ megabytes }}</template>
        <template v-if="releasedAt"> · rilis {{ releasedAt }}</template>
        · langsung dari GitHub
      </p>
    </section>

    <!-- Fitur: kisi garis rambut ala grid tanpa bayangan -->
    <section class="mx-auto w-full max-w-5xl px-4 sm:px-6">
      <div class="grid gap-px overflow-hidden rounded-2xl border border-border bg-border sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="feature in FEATURES" :key="feature.title" class="bg-surface p-6">
          <component :is="feature.icon" class="size-4 text-ink" />
          <h2 class="mt-4 text-sm font-medium text-ink">{{ feature.title }}</h2>
          <p class="mt-1.5 text-sm leading-relaxed text-ink-muted">{{ feature.body }}</p>
        </div>
      </div>
    </section>

    <!-- Dua aplikasi + reponya (isi halaman "tentang" yang lama, dilebur ke sini) -->
    <section class="mx-auto w-full max-w-5xl px-4 pt-20 sm:px-6">
      <h2 class="text-2xl font-semibold tracking-tight text-ink">Dua aplikasi, satu data.</h2>
      <p class="mt-2 max-w-2xl text-sm text-ink-muted">
        Yang dipakai jualan tiap hari ada di HP. Yang butuh layar besar — laporan, impor massal,
        hak akses — ada di web. Keduanya menyimpan data yang sama, dan dua-duanya open source:
        boleh dibaca, dipasang sendiri di server kamu, atau dikirimi perbaikan.
      </p>

      <div class="mt-6 grid gap-px overflow-hidden rounded-2xl border border-border bg-border sm:grid-cols-2">
        <div v-for="app in APPS" :key="app.repo" class="flex flex-col bg-surface p-6">
          <component :is="app.icon" class="size-4 text-ink" />
          <h3 class="mt-4 text-sm font-medium text-ink">{{ app.title }}</h3>
          <p class="mt-1.5 text-sm leading-relaxed text-ink-muted">{{ app.body }}</p>
          <p class="mt-3 font-mono text-xs text-ink-subtle">{{ app.stack }}</p>
          <a
            :href="app.url"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-5 flex items-center gap-2 font-mono text-xs text-ink-muted transition hover:text-ink"
          >
            <GithubIcon class="size-3.5 shrink-0" />
            <span class="truncate underline underline-offset-4">{{ app.repo }}</span>
          </a>
        </div>
      </div>
    </section>

    <!-- Penutup -->
    <section class="mx-auto w-full max-w-5xl px-4 py-20 sm:px-6">
      <!--
        Kartunya sengaja sewarna halaman (bukan `surface-raised`): di mode terang
        bidang putih di atas abu jadi kotak yang mengambang sendiri, padahal seluruh
        halaman ini disusun dari kisi garis rambut.
      -->
      <div class="rounded-2xl border border-border px-6 py-12 text-center">
        <h2 class="text-2xl font-semibold tracking-tight text-ink">Mulai hari ini.</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-ink-muted">
          Kalau terbantu dan mau ikut menutup biaya server,
          <Link :href="route('donate.index')" class="text-ink underline underline-offset-4">
            halaman dukungannya di sini </Link
          >— murni sukarela.
        </p>
        <div class="mt-7 flex flex-wrap items-center justify-center gap-2.5">
          <a :href="downloadUrl" target="_blank" rel="noopener noreferrer">
            <Button>
              <Download class="size-4" />
              {{ downloadLabel }}
            </Button>
          </a>
          <a
            :href="release.url"
            target="_blank"
            rel="noopener noreferrer"
            class="font-mono text-xs text-ink-subtle underline underline-offset-4 transition hover:text-ink"
          >
            semua rilis
          </a>
        </div>
      </div>
    </section>
  </GuestLayout>
</template>
