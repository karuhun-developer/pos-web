<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { SEQUENTIAL, TOKENS } from '@/charts/theme'
import { money, tooltipTitle } from '@/charts/format'
import { theme } from '@/lib/theme'
import type { HourlyHeatmap } from '@/types/reports'

const props = defineProps<{ heatmap: HourlyHeatmap }>()

const empty = computed(() => props.heatmap.max === 0)

// Magnitudo di sebuah grid → satu hue, terang→gelap. Bukan pelangi.
const option = computed(() => {
  const mode = theme.value
  const token = TOKENS[mode]

  return {
    grid: { left: 8, right: 8, top: 8, bottom: 56, containLabel: true },
    tooltip: {
      position: 'top',
      formatter: (params: { data: [number, number, number] }) => {
        const [hour, day, value] = params.data

        return (
          tooltipTitle(`${props.heatmap.days[day]} · ${props.heatmap.hours[hour]}.00`) +
          `<div style="margin-top:4px"><strong>${money(value)}</strong></div>`
        )
      },
    },
    xAxis: {
      type: 'category',
      data: props.heatmap.hours,
      splitArea: { show: false },
      axisLabel: { interval: 1 },
    },
    yAxis: { type: 'category', data: props.heatmap.days, splitArea: { show: false } },
    visualMap: {
      min: 0,
      max: props.heatmap.max,
      calculable: false,
      orient: 'horizontal',
      left: 'center',
      bottom: 0,
      itemHeight: 90,
      text: ['Ramai', 'Sepi'],
      inRange: { color: SEQUENTIAL[mode] },
      formatter: () => '',
    },
    series: [
      {
        type: 'heatmap',
        data: props.heatmap.cells,
        itemStyle: { borderColor: token.surface, borderWidth: 1, borderRadius: 2 },
        emphasis: { itemStyle: { borderColor: token.ink, borderWidth: 1 } },
        progressive: 0,
      },
    ],
  }
})

/**
 * Tabelnya memuat sel yang berisi saja, urut dari yang teramai — sel nol adalah
 * ketiadaan transaksi, bukan informasi yang perlu 168 baris untuk dibaca.
 */
const rows = computed(() =>
  props.heatmap.cells
    .filter(([, , value]) => value > 0)
    .sort((a, b) => b[2] - a[2])
    .map(([hour, day, value]) => ({
      key: `${day}-${hour}`,
      day: props.heatmap.days[day],
      hour: `${props.heatmap.hours[hour]}.00`,
      value,
    })),
)
</script>

<template>
  <ChartCard
    title="Jam ramai"
    description="Omzet per jam menurut hari dalam sepekan"
    :empty="empty"
  >
    <BaseChart :option="option" :height="300" />

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Hari</th>
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Jam</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Omzet</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.key" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ row.day }}</td>
            <td class="px-5 py-2 text-ink-muted tabular-nums">{{ row.hour }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ money(row.value) }}</td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
