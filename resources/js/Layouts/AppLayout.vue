<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
  ArrowLeftRight,
  BarChart3,
  Boxes,
  FileSpreadsheet,
  Heart,
  Info,
  LayoutDashboard,
  LogOut,
  Menu,
  Package,
  Settings,
  Shield,
  Wallet,
  X,
} from '@lucide/vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import FlashToast from '@/Components/FlashToast.vue'
import StoreSwitcher from '@/Components/StoreSwitcher.vue'
import type { SharedProps } from '@/types'

defineProps<{ title: string; subtitle?: string }>()

const page = usePage<SharedProps>()
const mobileNav = ref(false)

const user = computed(() => page.props.auth.user)

const ALL_NAV = [
  { name: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { name: 'products.index', label: 'Produk', icon: Package },
  { name: 'categories.index', label: 'Kategori', icon: Boxes },
  { name: 'sales.index', label: 'Transaksi', icon: ArrowLeftRight },
  { name: 'cashflow.index', label: 'Arus Kas', icon: Wallet },
  { name: 'reports.index', label: 'Laporan', icon: BarChart3, permission: 'reports.view' },
  { name: 'io.index', label: 'Impor/Ekspor', icon: FileSpreadsheet },
  { name: 'store.edit', label: 'Pengaturan Toko', icon: Settings },
]

/*
 * Menu yang ujungnya 403 tidak ditawarkan. Ini murni sopan santun UI —
 * penegakannya tetap di policy (ReportController → Sale::viewReports), jadi
 * mengetik URL-nya langsung pun tetap ditolak.
 */
const NAV = computed(() =>
  ALL_NAV.filter((item) => !item.permission || (user.value?.permissions.includes(item.permission) ?? false)),
)

/** Highlight nav: cocokkan prefix path supaya halaman detail ikut aktif. */
function isActive(name: string): boolean {
  const target = new URL(route(name), window.location.origin).pathname
  const current = page.url.split('?')[0]

  return target === '/dashboard' ? current === target : current.startsWith(target)
}

function logout() {
  router.post(route('logout'))
}
</script>

<template>
  <Head :title="title" />

  <div class="flex min-h-screen bg-surface">
    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-border bg-surface-raised
             transition-transform lg:static lg:translate-x-0"
      :class="mobileNav ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex h-16 shrink-0 items-center gap-2 border-b border-border px-5">
        <div class="flex size-8 items-center justify-center rounded-lg bg-brand text-brand-ink">
          <Package class="size-4" />
        </div>
        <span class="text-sm font-semibold text-ink">POS Pro</span>
        <button
          type="button"
          class="ml-auto text-ink-muted lg:hidden"
          aria-label="Tutup menu"
          @click="mobileNav = false"
        >
          <X class="size-5" />
        </button>
      </div>

      <div class="border-b border-border p-3">
        <StoreSwitcher />
      </div>

      <nav class="flex-1 space-y-0.5 overflow-y-auto p-3">
        <Link
          v-for="item in NAV"
          :key="item.name"
          :href="route(item.name)"
          class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition"
          :class="isActive(item.name)
            ? 'bg-brand-soft text-brand'
            : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'"
          @click="mobileNav = false"
        >
          <component :is="item.icon" class="size-4 shrink-0" />
          {{ item.label }}
        </Link>

        <Link
          v-if="user?.is_superadmin"
          :href="route('admin.dashboard')"
          class="mt-3 flex items-center gap-3 rounded-xl border border-dashed border-border-strong px-3 py-2
                 text-sm font-medium text-ink-muted transition hover:bg-surface-sunken hover:text-ink"
        >
          <Shield class="size-4 shrink-0" />
          Panel Superadmin
        </Link>
      </nav>

      <div class="space-y-0.5 border-t border-border p-3">
        <Link
          :href="route('donate.index')"
          class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-ink-muted
                 transition hover:bg-surface-sunken hover:text-ink"
        >
          <Heart class="size-4 shrink-0" />
          Dukung POS Pro
        </Link>
        <Link
          :href="route('about')"
          class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-ink-muted
                 transition hover:bg-surface-sunken hover:text-ink"
        >
          <Info class="size-4 shrink-0" />
          Tentang
        </Link>
      </div>
    </aside>

    <div v-if="mobileNav" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="mobileNav = false" />

    <!-- Konten -->
    <div class="flex min-w-0 flex-1 flex-col">
      <header
        class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-border
               bg-surface/85 px-4 backdrop-blur lg:px-8"
      >
        <button
          type="button"
          class="text-ink-muted lg:hidden"
          aria-label="Buka menu"
          @click="mobileNav = true"
        >
          <Menu class="size-5" />
        </button>

        <div class="min-w-0 flex-1">
          <h1 class="truncate text-base font-semibold text-ink">{{ title }}</h1>
          <p v-if="subtitle" class="truncate text-xs text-ink-muted">{{ subtitle }}</p>
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
          <slot name="actions" />
        </div>

        <ThemeToggle />

        <div class="flex items-center gap-2 border-l border-border pl-3">
          <img
            v-if="user?.avatar_url"
            :src="user.avatar_url"
            :alt="user.name"
            class="size-8 rounded-full object-cover"
          />
          <div
            v-else
            class="flex size-8 items-center justify-center rounded-full bg-surface-sunken text-xs font-semibold text-ink-muted"
          >
            {{ user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="hidden min-w-0 sm:block">
            <p class="truncate text-xs font-medium text-ink">{{ user?.name }}</p>
            <p class="truncate text-[11px] text-ink-subtle">{{ user?.email }}</p>
          </div>
          <button
            type="button"
            class="rounded-xl p-2 text-ink-muted transition hover:bg-surface-sunken hover:text-ink"
            aria-label="Keluar"
            title="Keluar"
            @click="logout"
          >
            <LogOut class="size-4" />
          </button>
        </div>
      </header>

      <main class="min-w-0 flex-1 p-4 lg:p-8">
        <slot />
      </main>
    </div>

    <FlashToast />
  </div>
</template>
