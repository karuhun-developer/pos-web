<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  BadgeCheck,
  BarChart3,
  Download,
  FileSpreadsheet,
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

const REPOS = [
  {
    url: props.repos.android,
    name: 'karuhun-developer/pos-android',
    body: 'Aplikasi kasirnya — Vue + Capacitor, database SQLite di HP.',
  },
  {
    url: props.repos.web,
    name: 'karuhun-developer/pos-web',
    body: 'POS Pro: backend sinkronisasi, panel toko, dan panel superadmin — Laravel.',
  },
]
</script>

<template>
  <GuestLayout
    title="Aplikasi kasir Android yang jalan tanpa internet"
    description="POS Kacaw: aplikasi kasir Android gratis yang tetap jalan offline, lalu sinkron
                 sendiri ke panel web untuk kelola produk dan baca laporan. Gratis 100%, sumber terbuka."
  >
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
        Gratis 100% · Sumber terbuka
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

    <!-- Dua aplikasi, satu data (isi halaman "tentang" yang lama, dilebur ke sini) -->
    <section class="mx-auto w-full max-w-5xl px-4 pt-20 sm:px-6">
      <h2 class="text-2xl font-semibold tracking-tight text-ink">Dua aplikasi, satu data.</h2>
      <p class="mt-2 max-w-xl text-sm text-ink-muted">
        Yang dipakai jualan tiap hari ada di HP. Yang butuh layar besar — laporan, impor massal,
        hak akses — ada di web. Keduanya menyimpan data yang sama.
      </p>

      <div class="mt-6 grid gap-px overflow-hidden rounded-2xl border border-border bg-border sm:grid-cols-2">
        <div class="bg-surface p-6">
          <Smartphone class="size-4 text-ink" />
          <h3 class="mt-4 text-sm font-medium text-ink">POS Kacaw · Android</h3>
          <p class="mt-1.5 text-sm leading-relaxed text-ink-muted">
            Kasir, keranjang, pindai barcode, cetak struk, sesi kasir, dan arus kas. Semua
            tersimpan di HP dan tetap bisa dipakai offline.
          </p>
        </div>
        <div class="bg-surface p-6">
          <Monitor class="size-4 text-ink" />
          <h3 class="mt-4 text-sm font-medium text-ink">POS Pro · panel web</h3>
          <p class="mt-1.5 text-sm leading-relaxed text-ink-muted">
            Backend sinkronisasinya, sekaligus tempat kelola produk, undang kasir, baca laporan
            mendalam, dan impor/ekspor massal.
          </p>
        </div>
      </div>
    </section>

    <!-- Kode sumber -->
    <section class="mx-auto w-full max-w-5xl px-4 pt-20 sm:px-6">
      <h2 class="text-2xl font-semibold tracking-tight text-ink">Kodenya kebuka.</h2>
      <p class="mt-2 max-w-xl text-sm text-ink-muted">
        Boleh dibaca, dipasang sendiri di server kamu, atau dikirimi perbaikan. Nemu bug? Buka
        issue — itu jalur tercepat sampai ke yang ngoding.
      </p>

      <div class="mt-6 grid gap-px overflow-hidden rounded-2xl border border-border bg-border sm:grid-cols-2">
        <a
          v-for="repo in REPOS"
          :key="repo.url"
          :href="repo.url"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-start gap-3 bg-surface p-6 transition hover:bg-surface-sunken"
        >
          <GithubIcon class="mt-0.5 size-4 shrink-0 text-ink" />
          <span class="min-w-0 flex-1">
            <span class="block truncate font-mono text-sm text-ink">{{ repo.name }}</span>
            <span class="mt-1.5 block text-sm leading-relaxed text-ink-muted">{{ repo.body }}</span>
          </span>
        </a>
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
