<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowRight, Package, ReceiptText, Wallet } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import StatTile from '@/Components/StatTile.vue'
import SalesTrendPanel from '@/Components/reports/SalesTrendPanel.vue'
import TopProductsPanel from '@/Components/reports/TopProductsPanel.vue'
import InventoryPanel from '@/Components/reports/InventoryPanel.vue'
import { formatNumber, formatRupiah } from '@/lib/utils'
import type { InventorySnapshot, SalesSummary, TopProducts } from '@/types/reports'

interface RecentSale {
  id: string
  number: string
  total: number
  payment_method: string
  status: 'completed' | 'void'
  /** Sudah diformat di server (zona toko) — jangan diformat ulang di sini. */
  sold_at: string
}

defineProps<{
  today: SalesSummary
  trend: SalesSummary['trend']
  top_products: TopProducts | null
  inventory: InventorySnapshot | null
  can_see_reports: boolean
  open_session: { id: string; opened_at: string; opening_cash: number; cashier: string | null } | null
  recent_sales: RecentSale[]
  counts: { products: number }
}>()

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
</script>

<template>
  <AppLayout title="Dashboard" subtitle="Bagaimana hari ini, dan apa yang perlu diurus sekarang">
    <!-- Sesi kasir yang masih terbuka adalah hal paling mendesak di halaman ini:
         selama belum ditutup, kas fisik belum pernah dicocokkan. -->
    <div
      v-if="open_session"
      class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-warning/40 bg-warning/10 px-5 py-4"
    >
      <div class="flex items-center gap-3">
        <Wallet class="size-5 shrink-0 text-warning" />
        <div>
          <p class="text-sm font-medium text-ink">Ada sesi kasir yang masih terbuka</p>
          <p class="text-xs text-ink-muted">
            Dibuka {{ open_session.opened_at }}
            <span v-if="open_session.cashier"> oleh {{ open_session.cashier }}</span>
            · modal awal {{ formatRupiah(open_session.opening_cash) }}
          </p>
        </div>
      </div>
      <Link
        :href="route('sessions.index')"
        class="inline-flex h-9 items-center gap-2 rounded-xl border border-border-strong bg-surface-raised px-3 text-xs font-medium text-ink hover:bg-surface-sunken"
      >
        Lihat sesi
        <ArrowRight class="size-4" />
      </Link>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <StatTile
        label="Omzet hari ini"
        :value="formatRupiah(today.kpi.revenue)"
        :delta="today.kpi.delta.revenue"
        delta-label="vs kemarin"
        :series="trend.current"
      />
      <StatTile
        label="Transaksi hari ini"
        :value="formatNumber(today.kpi.orders)"
        :delta="today.kpi.delta.orders"
        delta-label="vs kemarin"
      />
      <StatTile
        label="Rata-rata keranjang"
        :value="formatRupiah(today.kpi.basket)"
        :delta="today.kpi.delta.basket"
        delta-label="vs kemarin"
      />
      <StatTile label="Produk aktif" :value="formatNumber(counts.products)" hint="Yang bisa dijual di kasir" />
    </div>

    <div class="mt-4 space-y-4">
      <SalesTrendPanel :trend="trend" />

      <Card flush title="Transaksi terakhir" description="Delapan struk terbaru dari kasir">
        <template #actions>
          <Link :href="route('sales.index')" class="text-xs font-medium text-brand hover:underline">
            Lihat semua
          </Link>
        </template>

        <EmptyState
          v-if="!recent_sales.length"
          :icon="ReceiptText"
          title="Belum ada transaksi"
          description="Struk muncul di sini setelah aplikasi kasir menyinkronkan penjualannya."
        />

        <DataTable v-else :columns="COLUMNS" :rows="recent_sales">
          <template #cell-number="{ row }">
            <Link :href="route('sales.show', row.id)" class="font-medium text-brand hover:underline">
              {{ row.number }}
            </Link>
          </template>
          <template #cell-sold_at="{ row }">
            <span class="text-ink-muted">{{ row.sold_at }}</span>
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
      </Card>

      <!-- Panel di bawah ini adalah laporan; kasir tidak melihatnya sama sekali
           supaya dashboard tidak jadi pintu belakang ke /laporan. -->
      <template v-if="can_see_reports">
        <TopProductsPanel v-if="top_products" :top="top_products" />
        <InventoryPanel v-if="inventory" :inventory="inventory" />
      </template>

      <Card v-else>
        <EmptyState
          :icon="Package"
          title="Laporan lengkap dikunci"
          description="Minta pemilik toko kalau kamu perlu melihat produk terlaris dan nilai inventori."
        />
      </Card>
    </div>
  </AppLayout>
</template>
