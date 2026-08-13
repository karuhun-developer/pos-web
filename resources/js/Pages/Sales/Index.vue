<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { ReceiptText, Search } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import StatTile from '@/Components/StatTile.vue'
import { formatDateTime, formatNumber, formatRupiah } from '@/lib/utils'
import type { Paginated, Sale } from '@/types'

const props = defineProps<{
  sales: Paginated<Sale>
  filters: { q: string; status: string; method: string | null; from: string | null; to: string | null }
  summary: { orders: number; revenue: number }
  payment_methods: string[]
}>()

const search = ref(props.filters.q)
const status = ref(props.filters.status)
const method = ref(props.filters.method ?? '')
const from = ref(props.filters.from ?? '')
const to = ref(props.filters.to ?? '')

const COLUMNS = [
  { key: 'number', label: 'Nomor' },
  { key: 'sold_at', label: 'Waktu', hideOnMobile: true },
  { key: 'payment_method', label: 'Metode', hideOnMobile: true },
  { key: 'status', label: 'Status' },
  { key: 'total', label: 'Total', align: 'right' as const },
]

const METHOD_LABELS: Record<string, string> = {
  cash: 'Tunai',
  qris: 'QRIS',
  transfer: 'Transfer',
  card: 'Kartu',
}

function apply() {
  router.get(
    route('sales.index'),
    {
      q: search.value || undefined,
      status: status.value,
      method: method.value || undefined,
      from: from.value || undefined,
      to: to.value || undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true },
  )
}

watchDebounced(search, apply, { debounce: 350 })
watch([status, method, from, to], apply)
</script>

<template>
  <AppLayout title="Transaksi" subtitle="Struk yang masuk dari aplikasi kasir">
    <div class="grid gap-4 sm:grid-cols-3">
      <StatTile label="Transaksi selesai" :value="formatNumber(summary.orders)" />
      <StatTile label="Omzet (sesuai filter)" :value="formatRupiah(summary.revenue)" />
      <StatTile
        label="Rata-rata keranjang"
        :value="formatRupiah(summary.orders ? Math.round(summary.revenue / summary.orders) : 0)"
      />
    </div>

    <Card flush class="mt-4">
      <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
        <div class="relative min-w-48 flex-1">
          <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-subtle" />
          <input
            v-model="search"
            type="search"
            placeholder="Cari nomor struk…"
            class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised pr-3 pl-9 text-sm
                   text-ink placeholder:text-ink-subtle focus:border-brand focus:outline-none"
          />
        </div>

        <select
          v-model="status"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter status"
        >
          <option value="all">Semua status</option>
          <option value="completed">Selesai</option>
          <option value="void">Dibatalkan</option>
        </select>

        <select
          v-model="method"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter metode bayar"
        >
          <option value="">Semua metode</option>
          <option v-for="m in payment_methods" :key="m" :value="m">
            {{ METHOD_LABELS[m] ?? m }}
          </option>
        </select>

        <div class="flex items-center gap-2">
          <input
            v-model="from"
            type="date"
            aria-label="Dari tanggal"
            class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          />
          <span class="text-xs text-ink-subtle">s/d</span>
          <input
            v-model="to"
            type="date"
            aria-label="Sampai tanggal"
            class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          />
        </div>
      </div>

      <EmptyState
        v-if="!sales.data.length"
        :icon="ReceiptText"
        title="Tidak ada transaksi"
        description="Transaksi muncul di sini setelah aplikasi kasir menyinkronkan penjualannya."
      />

      <DataTable v-else :columns="COLUMNS" :rows="sales.data">
        <template #cell-number="{ row }">
          <Link :href="route('sales.show', row.id)" class="font-medium text-brand hover:underline">
            {{ row.number }}
          </Link>
        </template>
        <template #cell-sold_at="{ row }">
          <span class="text-ink-muted">{{ formatDateTime(row.sold_at) }}</span>
        </template>
        <template #cell-payment_method="{ row }">
          <span class="text-ink-muted">{{ METHOD_LABELS[row.payment_method] ?? row.payment_method }}</span>
        </template>
        <template #cell-status="{ row }">
          <Badge :tone="row.status === 'void' ? 'danger' : 'success'">
            {{ row.status === 'void' ? 'Dibatalkan' : 'Selesai' }}
          </Badge>
        </template>
        <template #cell-total="{ row }">
          <span class="font-medium" :class="row.status === 'void' ? 'text-ink-subtle line-through' : 'text-ink'">
            {{ formatRupiah(row.total) }}
          </span>
        </template>
      </DataTable>

      <Pagination :meta="sales" />
    </Card>
  </AppLayout>
</template>
