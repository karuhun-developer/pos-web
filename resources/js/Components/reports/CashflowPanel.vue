<script setup lang="ts">
import { computed } from 'vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import { BAR_RADIUS, DIVERGING } from '@/charts/theme'
import { compactRupiah, dayLabel, money, tooltipRow, tooltipTitle } from '@/charts/format'
import { theme } from '@/lib/theme'
import type { CashflowDaily } from '@/types/reports'

const props = defineProps<{ cashflow: CashflowDaily }>()

const empty = computed(
  () => props.cashflow.income.every((v) => v === 0) && props.cashflow.expense.every((v) => v === 0),
)

// Polaritas (hari ini surplus atau defisit) → batang menyebar dari nol dengan
// dua hue dan abu netral di garis nolnya.
const option = computed(() => {
  const mode = theme.value
  const pole = DIVERGING[mode]

  return {
    grid: { left: 8, right: 16, top: 36, bottom: 8, containLabel: true },
    legend: { top: 0, left: 0 },
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter: (params: Array<{ dataIndex: number }>) => {
        const index = params[0]?.dataIndex ?? 0

        return [
          tooltipTitle(props.cashflow.days[index] ?? ''),
          tooltipRow(pole.positive, 'Masuk', money(props.cashflow.income[index] ?? 0)),
          // Nilai dikirim negatif dari server; di tooltip ditampilkan apa adanya
          // supaya cocok dengan arah batangnya.
          tooltipRow(pole.negative, 'Keluar', money(props.cashflow.expense[index] ?? 0)),
          tooltipRow(pole.neutral, 'Bersih', money(props.cashflow.net[index] ?? 0)),
        ].join('')
      },
    },
    xAxis: {
      type: 'category',
      data: props.cashflow.days.map(dayLabel),
      axisLabel: { hideOverlap: true },
    },
    yAxis: { type: 'value', axisLabel: { formatter: (value: number) => compactRupiah(value) } },
    series: [
      {
        name: 'Uang masuk',
        type: 'bar',
        data: props.cashflow.income,
        color: pole.positive,
        barMaxWidth: 14,
        itemStyle: { borderRadius: [BAR_RADIUS, BAR_RADIUS, 0, 0] },
        markLine: {
          silent: true,
          symbol: 'none',
          data: [{ yAxis: 0 }],
          lineStyle: { color: pole.neutral, width: 1, type: 'solid' },
          label: { show: false },
        },
      },
      {
        name: 'Uang keluar',
        type: 'bar',
        data: props.cashflow.expense,
        color: pole.negative,
        barMaxWidth: 14,
        itemStyle: { borderRadius: [0, 0, BAR_RADIUS, BAR_RADIUS] },
      },
    ],
  }
})

const rows = computed(() =>
  props.cashflow.days.map((day, index) => ({
    day,
    income: props.cashflow.income[index] ?? 0,
    expense: props.cashflow.expense[index] ?? 0,
    net: props.cashflow.net[index] ?? 0,
  })),
)
</script>

<template>
  <ChartCard title="Arus kas harian" description="Uang masuk di atas nol, uang keluar di bawahnya" :empty="empty">
    <BaseChart :option="option" :height="260" />

    <template #table>
      <table class="w-full border-collapse text-sm">
        <thead class="sticky top-0 bg-surface-raised">
          <tr class="border-b border-border">
            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Tanggal</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Masuk</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Keluar</th>
            <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Bersih</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.day" class="border-b border-border last:border-0">
            <td class="px-5 py-2 text-ink">{{ row.day }}</td>
            <td class="px-5 py-2 text-right text-ink tabular-nums">{{ money(row.income) }}</td>
            <td class="px-5 py-2 text-right text-ink-muted tabular-nums">{{ money(row.expense) }}</td>
            <td
              class="px-5 py-2 text-right tabular-nums"
              :class="row.net < 0 ? 'text-danger' : 'text-ink'"
            >
              {{ money(row.net) }}
            </td>
          </tr>
        </tbody>
      </table>
    </template>
  </ChartCard>
</template>
