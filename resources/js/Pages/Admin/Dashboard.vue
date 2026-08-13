<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import StatTile from '@/Components/StatTile.vue'
import Badge from '@/Components/ui/Badge.vue'
import ChartCard from '@/Components/charts/ChartCard.vue'
import BaseChart from '@/Components/charts/BaseChart.vue'
import MonthlySeriesPanel from '@/Components/admin/MonthlySeriesPanel.vue'
import { CATEGORICAL } from '@/charts/theme'
import { monthLabel, tooltipRow, tooltipTitle } from '@/charts/format'
import { theme } from '@/lib/theme'
import { formatIsoDate, formatNumber, formatRupiah } from '@/lib/utils'
import { channelLabel, statusLabel, statusTone } from '@/lib/donation'

interface RecentStore {
  id: number
  name: string
  owner: string | null
  created_at: string | null
}

interface RecentDonation {
  id: number
  name: string
  amount: number
  channel: string
  status: string
  created_at: string | null
}

const props = defineProps<{
  kpi: {
    stores: number
    users: number
    products: number
    orders: number
    revenue: number
    donation_amount: number
    donation_count: number
  }
  growth: { months: string[]; stores: number[]; users: number[] }
  revenue: { months: string[]; values: number[] }
  donations: { months: string[]; values: number[] }
  recent_stores: RecentStore[]
  recent_donations: RecentDonation[]
}>()

const growthEmpty = computed(
  () => props.growth.stores.every((v) => v === 0) && props.growth.users.every((v) => v === 0),
)

// Dua seri, satuan sama (jumlah pendaftaran) → satu sumbu.
const growthOption = computed(() => {
  const palette = CATEGORICAL[theme.value]
  const labels = props.growth.months.map(monthLabel)

  return {
    grid: { left: 8, right: 16, top: 28, bottom: 8, containLabel: true },
    legend: { top: 0, left: 0, data: ['Toko baru', 'Pengguna baru'] },
    tooltip: {
      trigger: 'axis',
      formatter: (params: Array<{ dataIndex: number }>) => {
        const index = params[0]?.dataIndex ?? 0

        return [
          tooltipTitle(labels[index] ?? ''),
          tooltipRow(palette[0], 'Toko baru', formatNumber(props.growth.stores[index] ?? 0)),
          tooltipRow(palette[1], 'Pengguna baru', formatNumber(props.growth.users[index] ?? 0)),
        ].join('')
      },
    },
    xAxis: { type: 'category', data: labels, boundaryGap: false, axisLabel: { hideOverlap: true } },
    yAxis: { type: 'value', minInterval: 1 },
    series: [
      { name: 'Toko baru', type: 'line', data: props.growth.stores, color: palette[0] },
      { name: 'Pengguna baru', type: 'line', data: props.growth.users, color: palette[1] },
    ],
  }
})
</script>

<template>
  <AdminLayout title="Ringkasan platform" subtitle="Angka lintas toko, 12 bulan terakhir">
    <div class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatTile label="Toko" :value="formatNumber(kpi.stores)" :hint="`${formatNumber(kpi.users)} pengguna`" />
        <StatTile label="Produk" :value="formatNumber(kpi.products)" hint="Total lintas toko" />
        <StatTile
          label="Omzet platform"
          :value="formatRupiah(kpi.revenue)"
          :hint="`${formatNumber(kpi.orders)} transaksi selesai`"
        />
        <StatTile
          label="Donasi masuk"
          :value="formatRupiah(kpi.donation_amount)"
          :hint="`${formatNumber(kpi.donation_count)} dukungan`"
        />
      </div>

      <ChartCard
        title="Pertumbuhan toko & pengguna"
        description="Pendaftaran baru per bulan"
        :empty="growthEmpty"
      >
        <BaseChart :option="growthOption" :height="260" />

        <template #table>
          <table class="w-full border-collapse text-sm">
            <thead class="sticky top-0 bg-surface-raised">
              <tr class="border-b border-border">
                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-ink-muted uppercase">Bulan</th>
                <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Toko baru</th>
                <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-ink-muted uppercase">Pengguna baru</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(month, index) in growth.months" :key="month" class="border-b border-border last:border-0">
                <td class="px-5 py-2 text-ink">{{ monthLabel(month) }}</td>
                <td class="px-5 py-2 text-right text-ink tabular-nums">{{ formatNumber(growth.stores[index] ?? 0) }}</td>
                <td class="px-5 py-2 text-right text-ink tabular-nums">{{ formatNumber(growth.users[index] ?? 0) }}</td>
              </tr>
            </tbody>
          </table>
        </template>
      </ChartCard>

      <div class="grid gap-6 lg:grid-cols-2">
        <MonthlySeriesPanel
          title="Omzet seluruh toko"
          description="Transaksi selesai, per bulan"
          :months="revenue.months"
          :values="revenue.values"
          series-name="Omzet"
        />
        <MonthlySeriesPanel
          title="Donasi per bulan"
          description="Catatan manual + pembayaran online"
          :months="donations.months"
          :values="donations.values"
          series-name="Donasi"
          :hue="4"
        />
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <Card title="Toko terbaru" flush>
          <template #actions>
            <Link :href="route('admin.stores.index')" class="text-xs font-medium text-brand">
              Lihat semua
            </Link>
          </template>
          <ul class="divide-y divide-border">
            <li v-for="store in recent_stores" :key="store.id" class="flex items-center gap-3 px-5 py-3">
              <div class="min-w-0 flex-1">
                <Link :href="route('admin.stores.show', store.id)" class="truncate text-sm font-medium text-ink hover:underline">
                  {{ store.name }}
                </Link>
                <p class="truncate text-xs text-ink-subtle">{{ store.owner ?? 'Tanpa pemilik' }}</p>
              </div>
              <p class="shrink-0 text-xs text-ink-muted">{{ formatIsoDate(store.created_at) }}</p>
            </li>
            <li v-if="!recent_stores.length" class="px-5 py-8 text-center text-sm text-ink-subtle">
              Belum ada toko.
            </li>
          </ul>
        </Card>

        <Card title="Donasi terbaru" flush>
          <template #actions>
            <Link :href="route('admin.donations.index')" class="text-xs font-medium text-brand">
              Lihat semua
            </Link>
          </template>
          <ul class="divide-y divide-border">
            <li v-for="donation in recent_donations" :key="donation.id" class="flex items-center gap-3 px-5 py-3">
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink">{{ donation.name }}</p>
                <p class="truncate text-xs text-ink-subtle">
                  {{ formatIsoDate(donation.created_at) }} · {{ channelLabel(donation.channel) }}
                </p>
              </div>
              <p class="shrink-0 text-sm text-ink tabular-nums">{{ formatRupiah(donation.amount) }}</p>
              <Badge :tone="statusTone(donation.status)">{{ statusLabel(donation.status) }}</Badge>
            </li>
            <li v-if="!recent_donations.length" class="px-5 py-8 text-center text-sm text-ink-subtle">
              Belum ada donasi.
            </li>
          </ul>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
