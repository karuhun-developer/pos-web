<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ArrowLeft, Heart, LayoutDashboard, LogOut, RefreshCw, Store as StoreIcon, Users } from '@lucide/vue'
import Logo from '@/Components/Logo.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import FlashToast from '@/Components/FlashToast.vue'
import type { SharedProps } from '@/types'

defineProps<{ title: string; subtitle?: string }>()

const page = usePage<SharedProps>()
const user = computed(() => page.props.auth.user)

/** Donasi yang menunggu ditinjau — dibagikan lewat props platform. */
const pendingDonations = computed(() => page.props.platform?.pending_donations ?? 0)

const NAV = [
  { name: 'admin.dashboard', label: 'Ringkasan', icon: LayoutDashboard },
  { name: 'admin.stores.index', label: 'Toko', icon: StoreIcon },
  { name: 'admin.users.index', label: 'Pengguna', icon: Users },
  { name: 'admin.sync.index', label: 'Log sync', icon: RefreshCw },
  { name: 'admin.donations.index', label: 'Donasi', icon: Heart },
]

function isActive(name: string): boolean {
  const target = new URL(route(name), window.location.origin).pathname
  const current = page.url.split('?')[0]

  return target === '/admin' ? current === target : current.startsWith(target)
}
</script>

<template>
  <Head :title="title" />

  <!-- Area platform sengaja beda kelir: bar gelap di atas supaya jelas kalau
       yang dilihat ini data lintas toko, bukan toko sendiri. -->
  <div class="min-h-screen bg-surface">
    <header class="border-b border-border bg-ink text-surface-raised">
      <div class="mx-auto flex h-14 max-w-7xl items-center gap-4 px-4 lg:px-8">
        <span class="flex items-center gap-2 text-sm font-semibold">
          <Logo class="size-5" />
          POS Pro · Superadmin
        </span>

        <nav class="ml-4 hidden items-center gap-1 sm:flex">
          <Link
            v-for="item in NAV"
            :key="item.name"
            :href="route(item.name)"
            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition"
            :class="isActive(item.name) ? 'bg-white/15 font-medium' : 'opacity-70 hover:opacity-100'"
          >
            <component :is="item.icon" class="size-4" />
            {{ item.label }}
            <!-- Kelir lencana kebalikan dari bar (yang sendirinya membalik di
                 mode gelap), jadi kontrasnya aman tanpa token khusus. -->
            <span
              v-if="item.name === 'admin.donations.index' && pendingDonations"
              class="rounded-full bg-surface-raised px-1.5 py-0.5 text-[10px] leading-none font-semibold text-ink"
              :aria-label="`${pendingDonations} donasi menunggu ditinjau`"
            >
              {{ pendingDonations }}
            </span>
          </Link>
        </nav>

        <div class="ml-auto flex items-center gap-2">
          <Link
            :href="route('dashboard')"
            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm opacity-70 transition hover:opacity-100"
          >
            <ArrowLeft class="size-4" />
            <span class="hidden sm:inline">Ke toko saya</span>
          </Link>
          <button
            type="button"
            class="rounded-lg p-2 opacity-70 transition hover:opacity-100"
            aria-label="Keluar"
            @click="router.post(route('logout'))"
          >
            <LogOut class="size-4" />
          </button>
        </div>
      </div>

      <nav class="flex items-center gap-1 overflow-x-auto px-4 pb-2 sm:hidden">
        <Link
          v-for="item in NAV"
          :key="item.name"
          :href="route(item.name)"
          class="shrink-0 rounded-lg px-3 py-1.5 text-sm transition"
          :class="isActive(item.name) ? 'bg-white/15 font-medium' : 'opacity-70'"
        >
          {{ item.label }}
          <template v-if="item.name === 'admin.donations.index' && pendingDonations">
            ({{ pendingDonations }})
          </template>
        </Link>
      </nav>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-6 lg:px-8">
      <div class="mb-6 flex items-end justify-between gap-4">
        <div class="min-w-0">
          <h1 class="truncate text-xl font-semibold text-ink">{{ title }}</h1>
          <p v-if="subtitle" class="truncate text-sm text-ink-muted">{{ subtitle }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <slot name="actions" />
          <ThemeToggle />
        </div>
      </div>

      <slot />
    </div>

    <p class="pb-8 text-center text-xs text-ink-subtle">Masuk sebagai {{ user?.email }}</p>

    <FlashToast />
  </div>
</template>
