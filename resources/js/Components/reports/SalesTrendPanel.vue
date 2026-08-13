<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { CATEGORICAL, TOKENS } from '@/charts/theme'
import { compactRupiah, dayLabel, money, tooltipRow, tooltipTitle } from '@/charts/format'
import { theme } from '@/lib/theme'
import type { SalesSummary } from '@/types/reports'

const props = defineProps<{ trend: SalesSummary['trend'] }>()

const empty = computed(() => props.trend.current.every((value) => value === 0) && props.trend.previous.every((value) => value === 0))

// Dua seri, satu satuan (rupiah) → satu sumbu. Tidak pernah dua sumbu Y.
const option = computed(() => {
  const mode = theme.value
  const palette = CATEGORICAL[mode]
  const token = TOKENS[mode]
  const labels = props.trend.days.map(dayLabel)

  return {
    grid: { left: 8, right: 16, top: 28, bottom: 8, containLabel: true },
    legend: { top: 0, left: 0, data: ['Periode ini', 'Periode sebelumnya'] },
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'line' },
      formatter: (params: Array<{ dataIndex: number }>) => {
        const index = params[0]?.dataIndex ?? 0

        return [
          tooltipTitle(props.trend.days[index] ?? ''),
          tooltipRow(palette[0], 'Periode ini', money(props.trend.current[index] ?? 0)),
          tooltipRow(
            token.inkSubtle,
            `Sebelumnya (${props.trend.previous_days[index] ?? '—'})`,
            money(props.trend.previous[index] ?? 0),
          ),
        ].join('')
      },
    },
    xAxis: { type: 'category', data: labels, boundaryGap: false, axisLabel: { hideOverlap: true } },
    yAxis: { type: 'value', axisLabel: { formatter: (value: number) => compactRupiah(value) } },
    series: [
      {
        name: 'Periode ini',
        type: 'line',
        data: props.trend.current,
        color: palette[0],
        smooth: false,
        showSymbol: props.trend.days.length <= 31,
      },
      {
        // Pembanding sengaja abu netral, bukan hue kedua: ia latar, bukan
        // kategori yang setara.
        name: 'Periode sebelumnya',
        type: 'line',
        data: props.trend.previous,
        color: token.inkSubtle,
        lineStyle: { type: 'dashed' },
        showSymbol: false,
      },
    ],
  }
})

const rows = computed(() =>
  props.trend.days.map((day, index) => ({
    day,
    previousDay: props.trend.previous_days[index] ?? '—',
    current: props.trend.current[index] ?? 0,
    previous: props.trend.previous[index] ?? 0,
  })),
)
</script>

<template>
  <ChartCard
    title="Tren penjualan harian"
    description="Dibandingkan dengan periode sepanjang ini tepat sebelumnya"
    :empty="empty"
  >
    <BaseChart :option="option" :height="280" />

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Tanggal</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Periode ini</th>
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Pembanding</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Sebelumnya</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.day" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ row.day }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ money(row.current) }}</td>
            <td class="px-5 py-2 text-ink-subtle">{{ row.previousDay }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ money(row.previous) }}</td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
