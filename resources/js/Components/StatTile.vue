<script setup lang="ts">
import { computed } from 'vue'
import { ArrowDownRight, ArrowRight, ArrowUpRight } from '@lucide/vue'
import { formatDelta } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    label: string
    value: string
    /** Perubahan vs periode sebelumnya, dalam persen. */
    delta?: number | null
    deltaLabel?: string
    hint?: string
    /** Deret kecil untuk sparkline — bentuk tren, bukan angka presisi. */
    series?: number[]
    /** Naik = baik? Untuk pengeluaran, naik justru buruk. */
    higherIsBetter?: boolean
  }>(),
  { delta: null, deltaLabel: 'vs periode sebelumnya', higherIsBetter: true },
)

const direction = computed(() => {
  if (props.delta === null || props.delta === undefined || Math.abs(props.delta) < 0.05) return 'flat'

  return props.delta > 0 ? 'up' : 'down'
})

// Arah selalu ditemani panah + teks; warna bukan satu-satunya penanda.
const tone = computed(() => {
  if (direction.value === 'flat') return 'text-ink-subtle'
  const good = direction.value === 'up' ? props.higherIsBetter : !props.higherIsBetter

  return good ? 'text-success' : 'text-danger'
})

const icon = computed(() =>
  direction.value === 'up' ? ArrowUpRight : direction.value === 'down' ? ArrowDownRight : ArrowRight,
)

/** Sparkline dinormalisasi ke kotak 100×28; tanpa sumbu, tanpa label. */
const path = computed(() => {
  const points = props.series ?? []
  if (points.length < 2) return null

  const min = Math.min(...points)
  const max = Math.max(...points)
  const span = max - min || 1

  return points
    .map((value, index) => {
      const x = (index / (points.length - 1)) * 100
      const y = 26 - ((value - min) / span) * 24

      return `${index === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`
    })
    .join(' ')
})
</script>

<template>
  <div class="rounded-2xl border border-border bg-surface-raised p-4 shadow-sm">
    <p class="truncate text-xs font-medium text-ink-muted">{{ label }}</p>
    <p class="mt-1 text-2xl font-semibold tracking-tight text-ink tabular-nums">{{ value }}</p>

    <p v-if="delta !== null && delta !== undefined" class="mt-2 flex items-center gap-1 text-xs" :class="tone">
      <component :is="icon" class="size-3.5 shrink-0" />
      <span class="tabular-nums">{{ formatDelta(delta) }}</span>
      <span class="truncate text-ink-subtle">{{ deltaLabel }}</span>
    </p>
    <p v-else-if="hint" class="mt-2 truncate text-xs text-ink-subtle">{{ hint }}</p>

    <!-- Sparkline mengambil satu baris sendiri: berdampingan dengan teks delta,
         ia menyisakan lebar yang terlalu sempit dan labelnya patah dua baris. -->
    <svg
      v-if="path"
      class="mt-2 h-7 w-full overflow-visible"
      viewBox="0 0 100 28"
      preserveAspectRatio="none"
      aria-hidden="true"
    >
      <path :d="path" fill="none" stroke="var(--color-brand)" stroke-width="2"
            vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  </div>
</template>
