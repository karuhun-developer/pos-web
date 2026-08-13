<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { BAR_RADIUS, CATEGORICAL } from '@/charts/theme'
import { compactRupiah, money, monthLabel, tooltipRow, tooltipTitle } from '@/charts/format'
import { theme } from '@/lib/theme'
import { formatNumber } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    title: string
    description?: string
    months: string[]
    values: number[]
    /** Uang dirender sebagai rupiah; sisanya sebagai jumlah biasa. */
    kind?: 'money' | 'count'
    seriesName?: string
    /** Indeks hue kategorikal — dipilih pemanggil supaya warna melekat pada entitas. */
    hue?: number
  }>(),
  { kind: 'money', seriesName: 'Nilai', hue: 0 },
)

const empty = computed(() => props.values.every((value) => value === 0))
const format = (value: number) => (props.kind === 'money' ? money(value) : formatNumber(value))

// Satu seri = satu warna. Batang tidak diwarnai menurut besarnya (itu
// pengkodean ganda yang justru menyulitkan dibaca) — tingginya sudah bercerita.
const option = computed(() => {
  const color = CATEGORICAL[theme.value][props.hue]

  return {
    grid: { left: 8, right: 16, top: 20, bottom: 8, containLabel: true },
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter: (params: Array<{ dataIndex: number }>) => {
        const index = params[0]?.dataIndex ?? 0

        return [
          tooltipTitle(monthLabel(props.months[index] ?? '')),
          tooltipRow(color, props.seriesName, format(props.values[index] ?? 0)),
        ].join('')
      },
    },
    xAxis: { type: 'category', data: props.months.map(monthLabel), axisLabel: { hideOverlap: true } },
    yAxis: {
      type: 'value',
      axisLabel: {
        formatter: (value: number) => (props.kind === 'money' ? compactRupiah(value) : formatNumber(value)),
      },
    },
    series: [
      {
        name: props.seriesName,
        type: 'bar',
        data: props.values,
        color,
        barMaxWidth: 28,
        itemStyle: { borderRadius: [BAR_RADIUS, BAR_RADIUS, 0, 0] },
      },
    ],
  }
})
</script>

<template>
  <ChartCard :title="title" :description="description" :empty="empty">
    <BaseChart :option="option" :height="240" />

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">
              Bulan
            </th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">
              {{ seriesName }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(month, index) in months" :key="month" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ monthLabel(month) }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ format(values[index] ?? 0) }}</td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
