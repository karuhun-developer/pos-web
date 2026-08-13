<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { PackageSearch } from '@lucide/vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import StatTile from '@/Components/StatTile.vue'
import { formatNumber, formatRupiah } from '@/lib/utils'
import type { InventorySnapshot } from '@/types/reports'

defineProps<{ inventory: InventorySnapshot }>()

/*
 * Sengaja BUKAN chart. Yang dicari di sini adalah "produk mana yang harus
 * dibeli hari ini" — itu daftar nama, bukan bentuk; dan jumlah produknya jauh
 * lebih dari tujuh kelas. Yang layak jadi angka tunggal cuma nilai inventori.
 */
const COLUMNS = [
  { key: 'name', label: 'Produk' },
  { key: 'sku', label: 'SKU', hideOnMobile: true },
  { key: 'stock', label: 'Sisa', align: 'right' as const },
  { key: 'value', label: 'Nilai', align: 'right' as const, hideOnMobile: true },
]
</script>

<template>
  <div class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-3">
      <StatTile
        label="Nilai inventori"
        :value="formatRupiah(inventory.value)"
        hint="Sisa stok × harga modal, kondisi sekarang"
      />
      <StatTile label="Produk dilacak" :value="formatNumber(inventory.tracked)" hint="Yang stoknya diikuti" />
      <StatTile
        label="Stok habis"
        :value="formatNumber(inventory.out_of_stock)"
        :hint="`${inventory.low_total} produk di bawah ${inventory.threshold}`"
      />
    </div>

    <Card
      flush
      title="Stok menipis"
      :description="`Sisa ${inventory.threshold} ke bawah, diurutkan dari yang paling sedikit`"
    >
      <EmptyState
        v-if="!inventory.low.length"
        :icon="PackageSearch"
        title="Semua stok aman"
        description="Tidak ada produk yang sisanya di bawah ambang."
      />

      <template v-else>
        <DataTable :columns="COLUMNS" :rows="inventory.low">
          <template #cell-name="{ row }">
            <Link :href="route('products.edit', row.id)" class="font-medium text-brand hover:underline">
              {{ row.name }}
            </Link>
            <Badge v-if="!row.active" tone="neutral" class="ml-2">Nonaktif</Badge>
          </template>
          <template #cell-sku="{ row }">
            <span class="text-ink-muted">{{ row.sku ?? '—' }}</span>
          </template>
          <template #cell-stock="{ row }">
            <!-- Status dibawa badge bertulisan, bukan sekadar warna. -->
            <Badge :tone="row.stock <= 0 ? 'danger' : 'warning'">
              {{ row.stock <= 0 ? 'Habis' : `${formatNumber(row.stock)} tersisa` }}
            </Badge>
          </template>
          <template #cell-value="{ row }">
            <span class="text-ink-muted tabular-nums">{{ formatRupiah(row.value) }}</span>
          </template>
        </DataTable>

        <p v-if="inventory.low_total > inventory.low.length" class="border-t border-border px-5 py-3 text-xs text-ink-subtle">
          Menampilkan {{ inventory.low.length }} dari {{ inventory.low_total }} produk yang menipis.
        </p>
      </template>
    </Card>
  </div>
</template>
