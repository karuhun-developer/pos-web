<script setup lang="ts">
import { Globe, RefreshCw, Smartphone } from '@lucide/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import StatTile from '@/Components/StatTile.vue'
import { formatDateTime, formatNumber } from '@/lib/utils'

interface EntityRow {
  entity: string
  rows: number
  deleted: number
  last_update: number
}

interface DeviceRow {
  store_id: number
  store: string
  device: string
  label: string
  is_web: boolean
  changes: number
  last_seen: number
  last_entity: string | null
}

defineProps<{
  entities: EntityRow[]
  devices: DeviceRow[]
  totals: { devices: number; web_devices: number; rows: number }
}>()

/** Nama entity di kontrak sync memakai nama tabel; ini terjemahannya. */
const ENTITY_LABELS: Record<string, string> = {
  categories: 'Kategori',
  products: 'Produk',
  media: 'Gambar',
  cashier_sessions: 'Sesi kasir',
  sales: 'Transaksi',
  sale_items: 'Item transaksi',
  cashflow_categories: 'Kategori kas',
  cashflow_entries: 'Arus kas',
}

const ENTITY_COLUMNS = [
  { key: 'entity', label: 'Entity' },
  { key: 'rows', label: 'Row aktif', align: 'right' as const },
  { key: 'deleted', label: 'Tombstone', align: 'right' as const, hideOnMobile: true },
  { key: 'last_update', label: 'Perubahan terakhir', align: 'right' as const },
]

const DEVICE_COLUMNS = [
  { key: 'label', label: 'Perangkat' },
  { key: 'store', label: 'Toko', hideOnMobile: true },
  { key: 'changes', label: 'Ditulis', align: 'right' as const },
  { key: 'last_seen', label: 'Terakhir', align: 'right' as const },
]

function entityLabel(entity: string | null): string {
  if (!entity) return '—'

  return ENTITY_LABELS[entity] ?? entity
}
</script>

<template>
  <AdminLayout title="Log sync" subtitle="Perangkat mana yang menulis, dan kapan terakhir">
    <div class="grid gap-4 sm:grid-cols-3">
      <StatTile label="Perangkat menulis" :value="formatNumber(totals.devices)" hint="30 terakhir aktif ditampilkan" />
      <StatTile label="Di antaranya lewat web" :value="formatNumber(totals.web_devices)" />
      <StatTile label="Total row tersinkron" :value="formatNumber(totals.rows)" hint="tombstone tidak dihitung" />
    </div>

    <!--
      Tidak ada tabel log tersendiri: setiap row entity sudah membawa
      origin_device + updated_at, jadi halaman ini membaca datanya langsung.
      Tidak ada audit trail yang bisa berbeda cerita dengan datanya sendiri.
    -->
    <!-- Ditumpuk, bukan dua kolom: dengan empat kolom di tiap tabel, kartu
         selebar setengah layar memotong kolom waktunya. -->
    <div class="mt-4 space-y-4">
      <Card title="Perangkat" description="Diurut dari yang terakhir menulis" flush>
        <EmptyState
          v-if="!devices.length"
          :icon="RefreshCw"
          title="Belum ada tulisan masuk"
          description="Begitu sebuah perangkat atau halaman web menulis data, ia muncul di sini."
        />

        <DataTable v-else :columns="DEVICE_COLUMNS" :rows="devices">
          <template #cell-label="{ row }">
            <div class="flex items-center gap-2.5">
              <component
                :is="row.is_web ? Globe : Smartphone"
                class="size-4 shrink-0 text-ink-subtle"
                aria-hidden="true"
              />
              <div class="min-w-0">
                <p class="truncate font-medium text-ink">{{ row.label }}</p>
                <p class="truncate text-xs text-ink-subtle">
                  {{ row.is_web ? 'Web' : 'Aplikasi kasir' }} · terakhir: {{ entityLabel(row.last_entity) }}
                </p>
              </div>
            </div>
          </template>
          <template #cell-store="{ row }">
            <!-- Nama toko dipotong, bukan dibiarkan membungkus tiga baris —
                 tinggi baris yang tidak seragam bikin tabel susah dipindai. -->
            <span class="block max-w-[12rem] truncate text-ink-muted" :title="row.store">{{ row.store }}</span>
          </template>
          <template #cell-changes="{ row }">
            <span class="tabular-nums">{{ formatNumber(row.changes) }}</span>
          </template>
          <template #cell-last_seen="{ row }">
            <!-- Waktu tidak boleh membungkus: "14 Agu 2026, 02.53" yang patah
                 tiga baris bikin tinggi barisnya melompat-lompat. -->
            <span class="whitespace-nowrap text-ink-muted tabular-nums">{{ formatDateTime(row.last_seen) }}</span>
          </template>
        </DataTable>
      </Card>

      <Card title="Entity" description="Jumlah row & pergerakan terakhir se-platform" flush>
        <DataTable :columns="ENTITY_COLUMNS" :rows="entities">
          <template #cell-entity="{ row }">
            <div class="min-w-0">
              <p class="truncate font-medium text-ink">{{ entityLabel(row.entity) }}</p>
              <p class="truncate font-mono text-xs text-ink-subtle">{{ row.entity }}</p>
            </div>
          </template>
          <template #cell-rows="{ row }">
            <span class="tabular-nums">{{ formatNumber(row.rows) }}</span>
          </template>
          <template #cell-deleted="{ row }">
            <!-- Tombstone bukan data hilang: row-nya tetap ada supaya penghapusan
                 ikut tersinkron ke perangkat yang lama tidak online. -->
            <Badge v-if="row.deleted">{{ formatNumber(row.deleted) }}</Badge>
            <span v-else class="text-ink-subtle">—</span>
          </template>
          <template #cell-last_update="{ row }">
            <span class="whitespace-nowrap text-ink-muted tabular-nums">{{ formatDateTime(row.last_update) }}</span>
          </template>
        </DataTable>
      </Card>
    </div>
  </AdminLayout>
</template>
