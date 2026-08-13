<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { BAR_RADIUS, CATEGORICAL, inkOn, TOKENS } from '@/charts/theme'
import { money, tooltipRow, tooltipTitle } from '@/charts/format'
import { formatNumber } from '@/lib/utils'
import { theme } from '@/lib/theme'
import type { PaymentMix } from '@/types/reports'

const props = defineProps<{ mix: PaymentMix }>()

const empty = computed(() => props.mix.rows.length === 0)

/**
 * Slot warna ditempelkan ke metode bayarnya, bukan ke peringkatnya: kalau
 * bulan depan QRIS menyalip tunai, warnanya tidak ikut bertukar.
 */
const SLOT: Record<string, number> = { cash: 0, qris: 1, transfer: 2, card: 3 }

function colorFor(method: string, index: number, palette: string[]): string {
  return palette[SLOT[method] ?? Math.min(index + 4, palette.length - 1)]
}

// Part-to-whole → satu batang bertumpuk, bukan pie: membandingkan panjang jauh
// lebih mudah daripada membandingkan sudut.
/**
 * Rincian di bawah batang: satu batang bertumpuk cuma memberi proporsi, padahal
 * yang ditanya berikutnya selalu "berapa rupiahnya". Ini juga yang mengisi sisa
 * tinggi kartu ketika ia diregangkan sejajar panel di sebelahnya.
 */
const breakdown = computed(() =>
  props.mix.rows.map((row, index) => ({
    ...row,
    color: colorFor(row.method, index, CATEGORICAL[theme.value]),
  })),
)

const option = computed(() => {
  const mode = theme.value
  const palette = CATEGORICAL[mode]
  const token = TOKENS[mode]

  return {
    grid: { left: 8, right: 8, top: 36, bottom: 0, containLabel: true },
    legend: { top: 0, left: 0 },
    tooltip: {
      trigger: 'item',
      formatter: (params: { seriesIndex: number; color: string }) => {
        const row = props.mix.rows[params.seriesIndex]

        return (
          tooltipTitle(row.label) +
          tooltipRow(params.color, 'Omzet', money(row.revenue)) +
          tooltipRow(token.inkSubtle, 'Transaksi', formatNumber(row.orders)) +
          tooltipRow(token.inkSubtle, 'Porsi', `${row.share}%`)
        )
      },
    },
    xAxis: { type: 'value', show: false, max: props.mix.total },
    yAxis: { type: 'category', data: [''], axisLabel: { show: false } },
    series: props.mix.rows.map((row, index) => {
      const fill = colorFor(row.method, index, palette)

      return {
        name: row.label,
        type: 'bar',
        stack: 'mix',
        data: [row.revenue],
        barWidth: 28,
        itemStyle: { color: fill, borderRadius: BAR_RADIUS },
        // Label hanya di segmen yang cukup lebar — angka di setiap segmen justru
        // menumpuk dan tidak terbaca.
        label: {
          show: row.share >= 12,
          formatter: `${row.share}%`,
          color: inkOn(fill),
          fontSize: 11,
          fontWeight: 600,
        },
      }
    }),
  }
})
</script>

<template>
  <ChartCard title="Metode bayar" description="Komposisi omzet menurut cara pembayaran" :empty="empty">
    <BaseChart :option="option" :height="140" />

    <ul class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="row in breakdown" :key="row.method" class="rounded-xl bg-surface-sunken px-3 py-2">
        <p class="flex items-center gap-2 text-xs text-ink-muted">
          <span class="size-2 shrink-0 rounded-full" :style="{ background: row.color }" aria-hidden="true" />
          {{ row.label }}
        </p>
        <p class="mt-1 text-sm font-semibold text-ink tabular-nums">{{ money(row.revenue) }}</p>
        <p class="text-xs text-ink-subtle tabular-nums">{{ formatNumber(row.orders) }} transaksi · {{ row.share }}%</p>
      </li>
    </ul>

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Metode</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Transaksi</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Omzet</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Porsi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in mix.rows" :key="row.method" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ row.label }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ formatNumber(row.orders) }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ money(row.revenue) }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ row.share }}%</td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
