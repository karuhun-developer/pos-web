<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'
import type { ReportPeriod } from '@/types/reports'

// `routeName` ada supaya filter yang sama bisa dipakai halaman impor/ekspor —
// rentangnya ikut masuk ke tautan unduhan di sana.
const props = withDefaults(
  defineProps<{ period: ReportPeriod; routeName?: string }>(),
  { routeName: 'reports.index' },
)

// Satu filter men-scope SELURUH halaman: semua panel dihitung ulang dari
// rentang yang sama, jadi KPI dan grafik tidak pernah bercerita beda.
const PRESETS = [
  { value: 'today', label: 'Hari ini' },
  { value: '7d', label: '7 hari' },
  { value: '30d', label: '30 hari' },
  { value: '90d', label: '90 hari' },
] as const

const custom = ref(props.period.preset === 'custom')
const from = ref(props.period.from)
const to = ref(props.period.to)

watch(
  () => props.period,
  (value) => {
    from.value = value.from
    to.value = value.to
    custom.value = value.preset === 'custom'
  },
)

function go(query: Record<string, string>) {
  router.get(route(props.routeName), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function pick(preset: string) {
  custom.value = false
  go({ preset })
}

function applyCustom() {
  if (!from.value || !to.value) return

  go({ preset: 'custom', from: from.value, to: to.value })
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <div class="flex items-center gap-1 rounded-xl border border-border bg-surface-raised p-1">
      <button
        v-for="preset in PRESETS"
        :key="preset.value"
        type="button"
        :aria-pressed="!custom && period.preset === preset.value"
        :class="
          cn(
            'h-8 rounded-lg px-3 text-xs font-medium transition',
            !custom && period.preset === preset.value
              ? 'bg-brand-soft text-brand'
              : 'text-ink-muted hover:text-ink',
          )
        "
        @click="pick(preset.value)"
      >
        {{ preset.label }}
      </button>
      <button
        type="button"
        :aria-pressed="custom"
        :class="
          cn(
            'h-8 rounded-lg px-3 text-xs font-medium transition',
            custom ? 'bg-brand-soft text-brand' : 'text-ink-muted hover:text-ink',
          )
        "
        @click="custom = true"
      >
        Rentang
      </button>
    </div>

    <div v-if="custom" class="flex items-center gap-2">
      <input
        v-model="from"
        type="date"
        aria-label="Dari tanggal"
        class="h-9 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
        @change="applyCustom"
      />
      <span class="text-xs text-ink-subtle">s/d</span>
      <input
        v-model="to"
        type="date"
        aria-label="Sampai tanggal"
        class="h-9 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
        @change="applyCustom"
      />
    </div>

    <p v-else class="text-xs text-ink-subtle">{{ period.from }} s/d {{ period.to }} · {{ period.days }} hari</p>
  </div>
</template>
