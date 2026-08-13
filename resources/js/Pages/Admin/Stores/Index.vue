<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { Search, Store as StoreIcon } from '@lucide/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { formatIsoDate, formatNumber, formatRupiah } from '@/lib/utils'
import type { Paginated } from '@/types'

interface StoreRow {
  id: number
  name: string
  owner: { id: number; name: string; email: string } | null
  members: number
  products: number
  orders: number
  revenue: number
  created_at: string | null
}

const props = defineProps<{
  stores: Paginated<StoreRow>
  filters: { q: string }
}>()

const search = ref(props.filters.q)

const COLUMNS = [
  { key: 'name', label: 'Toko' },
  { key: 'owner', label: 'Pemilik', hideOnMobile: true },
  { key: 'members', label: 'Anggota', align: 'right' as const, hideOnMobile: true },
  { key: 'products', label: 'Produk', align: 'right' as const, hideOnMobile: true },
  { key: 'orders', label: 'Transaksi', align: 'right' as const },
  { key: 'revenue', label: 'Omzet', align: 'right' as const },
]

watchDebounced(
  search,
  () => {
    router.get(
      route('admin.stores.index'),
      { q: search.value || undefined },
      { preserveState: true, preserveScroll: true, replace: true },
    )
  },
  { debounce: 350 },
)
</script>

<template>
  <AdminLayout title="Toko" :subtitle="`${formatNumber(stores.total)} toko terdaftar`">
    <Card flush>
      <div class="border-b border-border p-4">
        <div class="relative max-w-sm">
          <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-subtle" />
          <input
            v-model="search"
            type="search"
            placeholder="Cari nama toko…"
            class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised pr-3 pl-9 text-sm
                   text-ink placeholder:text-ink-subtle focus:border-brand focus:outline-none"
          />
        </div>
      </div>

      <EmptyState
        v-if="!stores.data.length"
        :icon="StoreIcon"
        title="Tidak ada toko"
        description="Toko dibuat otomatis saat pengguna baru mendaftar."
      />

      <DataTable v-else :columns="COLUMNS" :rows="stores.data">
        <template #cell-name="{ row }">
          <Link :href="route('admin.stores.show', row.id)" class="font-medium text-brand hover:underline">
            {{ row.name }}
          </Link>
          <p class="text-xs text-ink-subtle">Dibuat {{ formatIsoDate(row.created_at) }}</p>
        </template>
        <template #cell-owner="{ row }">
          <template v-if="row.owner">
            <span class="text-ink">{{ row.owner.name }}</span>
            <p class="text-xs text-ink-subtle">{{ row.owner.email }}</p>
          </template>
          <span v-else class="text-ink-subtle">—</span>
        </template>
        <template #cell-members="{ row }">
          <span class="tabular-nums">{{ formatNumber(row.members) }}</span>
        </template>
        <template #cell-products="{ row }">
          <span class="tabular-nums">{{ formatNumber(row.products) }}</span>
        </template>
        <template #cell-orders="{ row }">
          <span class="tabular-nums">{{ formatNumber(row.orders) }}</span>
        </template>
        <template #cell-revenue="{ row }">
          <span class="font-medium tabular-nums">{{ formatRupiah(row.revenue) }}</span>
        </template>
      </DataTable>

      <Pagination :meta="stores" />
    </Card>
  </AdminLayout>
</template>
