<script setup lang="ts">
import { ref } from 'vue'
import { ChartColumnBig, Table2 } from '@lucide/vue'
import { cn } from '@/lib/utils'

withDefaults(
  defineProps<{
    title: string
    description?: string
    /** Tidak ada data pada rentang ini — tampilkan pesan, bukan chart kosong. */
    empty?: boolean
    emptyText?: string
  }>(),
  { empty: false, emptyText: 'Belum ada data pada rentang ini.' },
)

// Setiap chart wajib punya tampilan tabel: itu jalan keluar untuk pembaca yang
// tidak bisa mengandalkan warna, sekaligus tempat membaca angka persisnya.
const view = ref<'chart' | 'table'>('chart')
</script>

<template>
  <section class="rounded-2xl border border-border bg-surface-raised shadow-sm">
    <header class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
      <div class="min-w-0">
        <h2 class="truncate text-sm font-semibold text-ink">{{ title }}</h2>
        <p v-if="description" class="mt-0.5 text-xs text-ink-muted">{{ description }}</p>
      </div>

      <div
        v-if="!empty"
        class="flex shrink-0 items-center gap-1 rounded-xl border border-border p-1"
        role="group"
        aria-label="Tampilan data"
      >
        <button
          v-for="mode in (['chart', 'table'] as const)"
          :key="mode"
          type="button"
          :aria-pressed="view === mode"
          :title="mode === 'chart' ? 'Grafik' : 'Tabel'"
          :class="
            cn(
              'inline-flex size-7 items-center justify-center rounded-lg transition',
              view === mode ? 'bg-brand-soft text-brand' : 'text-ink-subtle hover:text-ink',
            )
          "
          @click="view = mode"
        >
          <component :is="mode === 'chart' ? ChartColumnBig : Table2" class="size-4" />
          <span class="sr-only">{{ mode === 'chart' ? 'Grafik' : 'Tabel' }}</span>
        </button>
      </div>
    </header>

    <p v-if="empty" class="px-5 py-12 text-center text-sm text-ink-subtle">{{ emptyText }}</p>

    <!-- Area plot memakai warna surface yang dipakai saat memvalidasi palet,
         bukan surface kartu — kontras seri yang diuji berlaku di sini. -->
    <div v-else-if="view === 'chart'" class="rounded-b-2xl bg-surface p-3">
      <slot />
    </div>

    <div v-else class="max-h-96 overflow-auto">
      <slot name="table" />
    </div>
  </section>
</template>
