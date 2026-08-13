<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { BAR_RADIUS, CATEGORICAL, TOKENS } from '@/charts/theme'
import { compactRupiah, money, tooltipRow, tooltipTitle } from '@/charts/format'
import { formatNumber } from '@/lib/utils'
import { theme } from '@/lib/theme'
import type { TopProducts } from '@/types/reports'

const props = defineProps<{ top: TopProducts }>()

const rows = computed(() => (props.top.other ? [...props.top.rows, props.top.other] : props.top.rows))

const empty = computed(() => rows.value.length === 0)

// Nama produk panjang dan yang dibaca adalah besarannya → batang horizontal.
// Satu seri, jadi tidak perlu legend: judul kartunya sudah menamai serinya.
const option = computed(() => {
  const mode = theme.value
  const palette = CATEGORICAL[mode]
  const token = TOKENS[mode]
  const data = [...rows.value].reverse()

  return {
    grid: { left: 8, right: 72, top: 8, bottom: 8, containLabel: true },
    tooltip: {
      trigger: 'item',
      formatter: (params: { dataIndex: number }) => {
        const row = data[params.dataIndex]

        return (
          tooltipTitle(row.name) +
          tooltipRow(palette[0], 'Omzet', money(row.revenue)) +
          tooltipRow(token.inkSubtle, 'Terjual', `${formatNumber(row.qty)} pcs`)
        )
      },
    },
    xAxis: { type: 'value', axisLabel: { formatter: (value: number) => compactRupiah(value) } },
    yAxis: {
      type: 'category',
      data: data.map((row) => row.name),
      axisLabel: { color: token.inkMuted, width: 140, overflow: 'truncate' },
    },
    series: [
      {
        type: 'bar',
        data: data.map((row, index) => ({
          value: row.revenue,
          // "Lainnya" bukan produk — ia agregat, jadi abu netral, bukan hue
          // kategorikal yang seolah setara dengan produk di atasnya.
          itemStyle:
            props.top.other && index === 0
              ? { color: token.inkSubtle }
              : { color: palette[0] },
        })),
        barMaxWidth: 18,
        itemStyle: { borderRadius: [0, BAR_RADIUS, BAR_RADIUS, 0] },
        // Label langsung: nilainya terbaca tanpa hover dan tanpa mengandalkan warna.
        label: {
          show: true,
          position: 'right',
          formatter: (params: { value: number }) => compactRupiah(params.value),
          color: token.inkMuted,
          fontSize: 11,
        },
      },
    ],
  }
})
</script>

<template>
  <ChartCard title="Produk terlaris" description="10 teratas menurut omzet; sisanya digabung" :empty="empty">
    <BaseChart :option="option" :height="Math.max(220, rows.length * 30 + 40)" />

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Produk</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Terjual</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Omzet</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.name" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ row.name }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ formatNumber(row.qty) }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ money(row.revenue) }}</td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
