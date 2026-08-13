<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeft, ReceiptText } from '@lucide/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import StatTile from '@/Components/StatTile.vue'
import { formatDateTime, formatIsoDate, formatIsoDateTime, formatNumber, formatRupiah } from '@/lib/utils'

interface Member {
  id: number
  name: string
  email: string
  role: string | null
  is_superadmin: boolean
}

interface SaleRow {
  id: string
  number: string
  total: number
  status: string
  payment_method: string
  sold_at: number
}

defineProps<{
  store: {
    id: number
    name: string
    owner: { id: number; name: string; email: string } | null
    created_at: string | null
  }
  kpi: { products: number; orders: number; revenue: number; cashflow_entries: number }
  last_activity: string | null
  members: Member[]
  recent_sales: SaleRow[]
}>()

const SALE_COLUMNS = [
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

const ROLE_LABELS: Record<string, string> = {
  owner: 'Pemilik',
  cashier: 'Kasir',
}
</script>

<template>
  <AdminLayout
    :title="store.name"
    :subtitle="`Terdaftar ${formatIsoDate(store.created_at)}${store.owner ? ` · ${store.owner.name}` : ''}`"
  >
    <template #actions>
      <Link
        :href="route('admin.stores.index')"
        class="inline-flex items-center gap-2 rounded-xl border border-border-strong bg-surface-raised px-3 py-2 text-sm text-ink"
      >
        <ArrowLeft class="size-4" />
        Kembali
      </Link>
    </template>

    <div class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatTile label="Produk aktif" :value="formatNumber(kpi.products)" />
        <StatTile label="Transaksi selesai" :value="formatNumber(kpi.orders)" />
        <StatTile label="Omzet" :value="formatRupiah(kpi.revenue)" />
        <StatTile
          label="Catatan arus kas"
          :value="formatNumber(kpi.cashflow_entries)"
          :hint="`Aktivitas terakhir ${formatIsoDateTime(last_activity)}`"
        />
      </div>

      <div class="grid gap-6 lg:grid-cols-5">
        <Card title="Anggota" description="Peran di toko ini" flush class="lg:col-span-2">
          <ul class="divide-y divide-border">
            <li v-for="member in members" :key="member.id" class="flex items-center gap-3 px-5 py-3">
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink">{{ member.name }}</p>
                <p class="truncate text-xs text-ink-subtle">{{ member.email }}</p>
              </div>
              <Badge v-if="member.is_superadmin" tone="brand">Superadmin</Badge>
              <Badge :tone="member.role === 'owner' ? 'success' : 'neutral'">
                {{ ROLE_LABELS[member.role ?? ''] ?? member.role ?? 'Tanpa peran' }}
              </Badge>
            </li>
            <li v-if="!members.length" class="px-5 py-8 text-center text-sm text-ink-subtle">
              Toko ini belum punya anggota.
            </li>
          </ul>
        </Card>

        <Card title="Transaksi terakhir" description="10 struk terbaru" flush class="lg:col-span-3">
          <EmptyState
            v-if="!recent_sales.length"
            :icon="ReceiptText"
            title="Belum ada transaksi"
            description="Struk muncul di sini setelah aplikasi kasir menyinkronkan penjualannya."
          />

          <DataTable v-else :columns="SALE_COLUMNS" :rows="recent_sales">
            <template #cell-number="{ row }">
              <span class="font-medium text-ink">{{ row.number }}</span>
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
              <span
                class="font-medium tabular-nums"
                :class="row.status === 'void' ? 'text-ink-subtle line-through' : 'text-ink'"
              >
                {{ formatRupiah(row.total) }}
              </span>
            </template>
          </DataTable>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
