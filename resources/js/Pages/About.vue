<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Download, ExternalLink, Heart } from '@lucide/vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import GithubIcon from '@/Components/GithubIcon.vue'
import Button from '@/Components/ui/Button.vue'

const props = defineProps<{
  app: {
    version: string
    repository: string
    android_repository: string
    android_download: string
  }
}>()

const REPOS = [
  {
    url: props.app.repository,
    name: 'karuhun-developer/pos-web',
    body: 'Backend & web POS Pro — Laravel, API sinkronisasi, panel toko dan superadmin.',
  },
  {
    url: props.app.android_repository,
    name: 'karuhun-developer/pos-android',
    body: 'POS Kacaw, aplikasi kasirnya — Vue + Capacitor, jalan offline di Android.',
  },
]
</script>

<template>
  <GuestLayout title="Tentang">
    <template #actions>
      <Link :href="route('donate.index')">
        <Button variant="ghost" size="sm">
          <Heart class="size-4" />
          Dukung
        </Button>
      </Link>
    </template>

    <section class="mx-auto w-full max-w-2xl px-4 py-12 lg:py-16">
      <span
        class="inline-flex items-center rounded-full bg-brand-soft px-3 py-1 font-mono text-xs font-medium text-brand"
      >
        v{{ app.version }}
      </span>

      <h1 class="mt-4 text-2xl font-semibold text-ink sm:text-3xl">Tentang POS Pro</h1>

      <p class="mt-3 text-sm leading-relaxed text-ink-muted">
        POS Pro adalah backend dan panel web untuk <strong class="text-ink">POS Kacaw</strong>,
        aplikasi kasir yang tetap jalan tanpa internet. Transaksi dicatat di HP, lalu naik ke
        sini begitu ada sinyal — dan sebaliknya, produk yang kamu tambah di web langsung terpakai
        di HP. Di layar besar kamu dapat yang tidak muat di aplikasi kasir: laporan mendalam,
        impor/ekspor massal, dan pengaturan hak akses per peran.
      </p>

      <p class="mt-3 text-sm leading-relaxed text-ink-muted">
        Gratis 100% — tidak ada fitur yang dikunci di baliknya.
      </p>

      <!-- Halaman rilis, bukan berkas APK versi tertentu: tautan ke satu berkas
           jadi basi tiap rilis dan diam-diam membagikan versi lama. -->
      <a
        :href="app.android_download"
        target="_blank"
        rel="noopener noreferrer"
        class="mt-6 inline-block"
      >
        <Button>
          <Download class="size-4" />
          Unduh aplikasi Android
        </Button>
      </a>
      <p class="mt-2 text-xs text-ink-subtle">
        Mengarah ke rilis terbaru di GitHub — APK-nya ada di bagian <em>Assets</em>.
      </p>

      <h2 class="mt-10 text-sm font-semibold text-ink">Kode sumber</h2>
      <div class="mt-3 space-y-3">
        <a
          v-for="repo in REPOS"
          :key="repo.url"
          :href="repo.url"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-start gap-3 rounded-2xl border border-border bg-surface-raised p-4
                 transition hover:border-border-strong hover:bg-surface-sunken"
        >
          <GithubIcon class="mt-0.5 size-5 shrink-0 text-ink" />
          <div class="min-w-0 flex-1">
            <p class="flex items-center gap-1.5 text-sm font-medium text-ink">
              {{ repo.name }}
              <ExternalLink class="size-3.5 text-ink-subtle" />
            </p>
            <p class="mt-0.5 text-sm text-ink-muted">{{ repo.body }}</p>
          </div>
        </a>
      </div>

      <p class="mt-6 text-xs text-ink-subtle">
        Nemu bug atau punya usul? Buka issue di repo — itu jalur tercepat sampai ke yang ngoding.
      </p>
    </section>
  </GuestLayout>
</template>
