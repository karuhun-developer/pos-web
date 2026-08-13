<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ClipboardList } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { formatDateTime, formatNumber, formatRupiah } from '@/lib/utils'
import type { CashierSession, Paginated } from '@/types'

const props = defineProps<{
  sessions: Paginated<CashierSession>
  filters: { status: string }
}>()

const status = ref(props.filters.status)

const COLUMNS = [
  { key: 'opened_at', label: 'Dibuka' },
  { key: 'opened_by', label: 'Kasir', hideOnMobile: true },
  { key: 'orders_count', label: 'Transaksi', align: 'right' as const, hideOnMobile: true },
  { key: 'revenue', label: 'Omzet', align: 'right' as const },
  { key: 'difference', label: 'Selisih laci', align: 'right' as const },
  { key: 'status', label: 'Status' },
]

watch(status, () => {
  router.get(
    route('sessions.index'),
    { status: status.value },
    { preserveState: true, preserveScroll: true, replace: true },
  )
})
</script>

<template>
  <AppLayout title="Sesi Kasir" subtitle="Riwayat buka-tutup laci; hanya bisa dibaca dari web">
    <Card flush>
      <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
        <select
          v-model="status"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter status sesi"
        >
          <option value="all">Semua sesi</option>
          <option value="open">Masih terbuka</option>
          <option value="closed">Sudah ditutup</option>
        </select>
      </div>

      <EmptyState
        v-if="!sessions.data.length"
        :icon="ClipboardList"
        title="Belum ada sesi kasir"
        description="Sesi tercatat ketika kasir membuka laci di aplikasi Android."
      />

      <DataTable v-else :columns="COLUMNS" :rows="sessions.data">
        <template #cell-opened_at="{ row }">
          <span class="text-ink">{{ formatDateTime(row.opened_at) }}</span>
        </template>
        <template #cell-opened_by="{ row }">
          <span class="text-ink-muted">{{ row.opened_by ?? '—' }}</span>
        </template>
        <template #cell-orders_count="{ row }">
          <span class="text-ink-muted tabular-nums">{{ formatNumber(row.orders_count ?? 0) }}</span>
        </template>
        <template #cell-revenue="{ row }">
          <span class="font-medium text-ink tabular-nums">{{ formatRupiah(row.revenue ?? 0) }}</span>
        </template>
        <template #cell-difference="{ row }">
          <span
            v-if="row.status === 'closed'"
            class="tabular-nums"
            :class="row.difference === 0
              ? 'text-ink-muted'
              : (row.difference ?? 0) > 0 ? 'text-success' : 'text-danger'"
          >
            {{ (row.difference ?? 0) > 0 ? '+' : '' }}{{ formatRupiah(row.difference ?? 0) }}
          </span>
          <span v-else class="text-ink-subtle">—</span>
        </template>
        <template #cell-status="{ row }">
          <Badge :tone="row.status === 'open' ? 'warning' : 'neutral'">
            {{ row.status === 'open' ? 'Terbuka' : 'Ditutup' }}
          </Badge>
        </template>
      </DataTable>

      <Pagination :meta="sessions" />
    </Card>
  </AppLayout>
</template>
