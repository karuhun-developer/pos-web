<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { BAR_RADIUS, CATEGORICAL, TOKENS } from '@/charts/theme'
import { compactRupiah, money, tooltipRow, tooltipTitle } from '@/charts/format'
import { theme } from '@/lib/theme'
import type { CategoryMargin } from '@/types/reports'

const props = defineProps<{ margin: CategoryMargin }>()

const empty = computed(() => props.margin.rows.length === 0)

// Part-to-whole per kategori: panjang total = omzet, terbagi jadi modal dan
// margin. Nama kategori panjang → batangnya horizontal.
const option = computed(() => {
  const mode = theme.value
  const palette = CATEGORICAL[mode]
  const token = TOKENS[mode]
  const data = [...props.margin.rows].reverse()

  return {
    grid: { left: 8, right: 16, top: 36, bottom: 8, containLabel: true },
    legend: { top: 0, left: 0 },
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter: (params: Array<{ dataIndex: number }>) => {
        const row = data[params[0]?.dataIndex ?? 0]

        return (
          tooltipTitle(row.name) +
          tooltipRow(palette[1], 'Modal (estimasi)', money(row.cost)) +
          tooltipRow(palette[0], 'Margin', money(row.margin)) +
          tooltipRow(token.inkSubtle, 'Omzet', `${money(row.revenue)} · ${row.margin_pct}%`)
        )
      },
    },
    xAxis: { type: 'value', axisLabel: { formatter: (value: number) => compactRupiah(value) } },
    yAxis: {
      type: 'category',
      data: data.map((row) => row.name),
      axisLabel: { color: token.inkMuted, width: 120, overflow: 'truncate' },
    },
    series: [
      {
        name: 'Modal (estimasi)',
        type: 'bar',
        stack: 'total',
        data: data.map((row) => row.cost),
        color: palette[1],
        barMaxWidth: 18,
        // Ujung yang menempel baseline tetap siku; yang dibulatkan ujung datanya.
        itemStyle: { borderRadius: 0 },
      },
      {
        name: 'Margin',
        type: 'bar',
        stack: 'total',
        data: data.map((row) => row.margin),
        color: palette[0],
        barMaxWidth: 18,
        itemStyle: { borderRadius: [0, BAR_RADIUS, BAR_RADIUS, 0] },
        label: {
          show: true,
          position: 'right',
          formatter: (params: { dataIndex: number }) => `${data[params.dataIndex].margin_pct}%`,
          color: token.inkMuted,
          fontSize: 11,
        },
      },
    ],
  }
})
</script>

<template>
  <ChartCard
    title="Modal vs margin per kategori"
    description="Modal memakai harga modal produk saat ini, jadi angkanya estimasi"
    :empty="empty"
  >
    <BaseChart :option="option" :height="Math.max(220, margin.rows.length * 34 + 60)" />

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Kategori</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Omzet</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Modal</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Margin</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">%</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in margin.rows" :key="row.name" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ row.name }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ money(row.revenue) }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ money(row.cost) }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ money(row.margin) }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ row.margin_pct }}%</td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
