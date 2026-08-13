<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { BAR_RADIUS, DIVERGING } from '@/charts/theme'
import { compactRupiah, money, tooltipRow, tooltipTitle } from '@/charts/format'
import { theme } from '@/lib/theme'
import type { SessionVariance } from '@/types/reports'

const props = defineProps<{ sessions: SessionVariance }>()

const empty = computed(() => props.sessions.rows.length === 0)

// Selisih laci berpolaritas (lebih / kurang), jadi digambar menyebar dari nol.
// Panjang batang = besar selisih, arah = tanda; nol berarti pas.
const option = computed(() => {
  const mode = theme.value
  const pole = DIVERGING[mode]

  return {
    grid: { left: 8, right: 16, top: 12, bottom: 8, containLabel: true },
    tooltip: {
      trigger: 'item',
      formatter: (params: { dataIndex: number }) => {
        const row = props.sessions.rows[params.dataIndex]

        return (
          tooltipTitle(`${row.label}${row.cashier ? ` · ${row.cashier}` : ''}`) +
          tooltipRow(pole.neutral, 'Seharusnya', money(row.expected)) +
          tooltipRow(pole.neutral, 'Dihitung', money(row.counted)) +
          tooltipRow(row.difference < 0 ? pole.negative : pole.positive, 'Selisih', money(row.difference))
        )
      },
    },
    xAxis: {
      type: 'category',
      data: props.sessions.rows.map((row) => row.label),
      axisLabel: { hideOverlap: true },
    },
    yAxis: { type: 'value', axisLabel: { formatter: (value: number) => compactRupiah(value) } },
    series: [
      {
        name: 'Selisih laci',
        type: 'bar',
        barMaxWidth: 16,
        data: props.sessions.rows.map((row) => ({
          value: row.difference,
          itemStyle: {
            color: row.difference < 0 ? pole.negative : row.difference > 0 ? pole.positive : pole.neutral,
            borderRadius:
              row.difference < 0 ? [0, 0, BAR_RADIUS, BAR_RADIUS] : [BAR_RADIUS, BAR_RADIUS, 0, 0],
          },
        })),
        markLine: {
          silent: true,
          symbol: 'none',
          data: [{ yAxis: 0 }],
          lineStyle: { color: pole.neutral, width: 1 },
          label: { show: false },
        },
      },
    ],
  }
})
</script>

<template>
  <ChartCard
    title="Selisih laci per sesi"
    :description="`${sessions.balanced} pas · ${sessions.short} kurang · ${sessions.over} lebih`"
    :empty="empty"
    empty-text="Belum ada sesi kasir yang ditutup pada rentang ini."
  >
    <BaseChart :option="option" :height="240" />

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Sesi</th>
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Kasir</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Seharusnya</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Dihitung</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Selisih</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in sessions.rows" :key="row.id" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ row.label }}</td>
            <td class="px-5 py-2 text-ink-muted">{{ row.cashier ?? '—' }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ money(row.expected) }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ money(row.counted) }}</td>
            <td
              class="px-5 py-2 text-right tabular-nums"
              :class="row.difference < 0 ? 'text-danger' : row.difference > 0 ? 'text-brand' : 'text-ink-subtle'"
            >
              {{ money(row.difference) }}
            </td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
