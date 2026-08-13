<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  BarChart3,
  Download,
  FileSpreadsheet,
  Heart,
  RefreshCw,
  ShieldCheck,
  Smartphone,
} from '@lucide/vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/Button.vue'
import type { SharedProps } from '@/types'

defineProps<{ googleEnabled: boolean; androidDownload: string }>()

const page = usePage<SharedProps>()
const loggedIn = computed(() => page.props.auth.user !== null)

const FEATURES = [
  {
    icon: RefreshCw,
    title: 'Offline dulu, sinkron belakangan',
    body: 'Kasir jalan tanpa internet di Android. Begitu online, data naik dan turun sendiri.',
  },
  {
    icon: Smartphone,
    title: 'Satu data, dua layar',
    body: 'Tambah produk di web, langsung kepakai di HP. Jualan di HP, laporannya kebaca di web.',
  },
  {
    icon: BarChart3,
    title: 'Laporan yang enak dibaca',
    body: 'Tren omzet, jam ramai, produk terlaris, margin per kategori — lengkap dengan tabelnya.',
  },
  {
    icon: FileSpreadsheet,
    title: 'Impor & ekspor massal',
    body: 'Unggah ribuan produk dari CSV/XLSX dengan pratinjau per baris sebelum diterapkan.',
  },
  {
    icon: ShieldCheck,
    title: 'Akses per peran',
    body: 'Pemilik dan kasir punya hak berbeda. Data toko lain tidak pernah bisa disentuh.',
  },
  {
    icon: Heart,
    title: '100% gratis',
    body: 'Semua fiturnya kebuka buat semua orang. Tidak ada yang dikunci di balik bayaran.',
  },
]
</script>

<template>
  <GuestLayout title="POS Pro">
    <template #actions>
      <Link v-if="loggedIn" :href="route('dashboard')">
        <Button size="sm">Buka Dashboard</Button>
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

    <section class="mx-auto w-full max-w-5xl px-4 py-16 text-center lg:py-24">
      <span
        class="inline-flex items-center gap-2 rounded-full bg-brand-soft px-3 py-1 text-xs font-medium text-brand"
      >
        Backend & web resmi POS Kacaw
      </span>
      <h1 class="mt-5 text-3xl leading-tight font-semibold text-ink sm:text-5xl">
        Kasir di HP, kendali penuh di web.
      </h1>
      <p class="mx-auto mt-4 max-w-2xl text-base text-ink-muted">
        POS Pro menyimpan data toko kamu, menyinkronkannya ke aplikasi Android, dan memberi
        laporan yang jauh lebih dalam di layar besar.
      </p>
      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <Link :href="loggedIn ? route('dashboard') : route('register')">
          <Button>{{ loggedIn ? 'Buka Dashboard' : 'Mulai gratis' }}</Button>
        </Link>
        <!-- Rilis terbaru di GitHub, bukan berkas APK versi tertentu: tautan
             ke satu berkas jadi basi tiap rilis dan diam-diam membagikan
             versi lama. -->
        <a :href="androidDownload" target="_blank" rel="noopener noreferrer">
          <Button variant="outline">
            <Download class="size-4" />
            Unduh aplikasi Android
          </Button>
        </a>
        <Link :href="route('donate.index')">
          <Button variant="ghost">
            <Heart class="size-4" />
            Dukung proyek ini
          </Button>
        </Link>
      </div>
    </section>

    <section class="mx-auto w-full max-w-5xl px-4 pb-20">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="feature in FEATURES"
          :key="feature.title"
          class="rounded-2xl border border-border bg-surface-raised p-5"
        >
          <component :is="feature.icon" class="size-5 text-brand" />
          <h2 class="mt-3 text-sm font-semibold text-ink">{{ feature.title }}</h2>
          <p class="mt-1 text-sm text-ink-muted">{{ feature.body }}</p>
        </div>
      </div>
    </section>
  </GuestLayout>
</template>
