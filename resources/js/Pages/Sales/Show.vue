<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Ban } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Modal from '@/Components/ui/Modal.vue'
import { formatDateTime, formatNumber, formatRupiah } from '@/lib/utils'
import type { Sale, SaleItem } from '@/types'

const props = defineProps<{
  sale: Sale
  items: SaleItem[]
  can_void: boolean
}>()

const confirming = ref(false)

const METHOD_LABELS: Record<string, string> = {
  cash: 'Tunai',
  qris: 'QRIS',
  transfer: 'Transfer',
  card: 'Kartu',
}

function voidSale() {
  router.post(route('sales.void', props.sale.id), {}, {
    preserveScroll: true,
    onFinish: () => (confirming.value = false),
  })
}
</script>

<template>
  <AppLayout :title="`Struk ${sale.number}`" :subtitle="formatDateTime(sale.sold_at)">
    <template #actions>
      <Link :href="route('sales.index')">
        <Button variant="ghost" size="sm">
          <ArrowLeft class="size-4" />
          Kembali
        </Button>
      </Link>
      <Button v-if="can_void" variant="danger" size="sm" @click="confirming = true">
        <Ban class="size-4" />
        Batalkan
      </Button>
    </template>

    <div class="grid gap-4 lg:grid-cols-3">
      <Card flush class="lg:col-span-2">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="border-b border-border">
              <th scope="col" class="px-5 py-3 text-left text-xs font-medium tracking-wide text-ink-muted uppercase">
                Item
              </th>
              <th scope="col" class="px-5 py-3 text-right text-xs font-medium tracking-wide text-ink-muted uppercase">
                Qty
              </th>
              <th scope="col" class="px-5 py-3 text-right text-xs font-medium tracking-wide text-ink-muted uppercase">
                Harga
              </th>
              <th scope="col" class="px-5 py-3 text-right text-xs font-medium tracking-wide text-ink-muted uppercase">
                Subtotal
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" class="border-b border-border last:border-0">
              <td class="px-5 py-3 text-ink">{{ item.name_snapshot }}</td>
              <td class="px-5 py-3 text-right text-ink-muted tabular-nums">{{ formatNumber(item.qty) }}</td>
              <td class="px-5 py-3 text-right text-ink-muted tabular-nums">
                {{ formatRupiah(item.price_snapshot) }}
              </td>
              <td class="px-5 py-3 text-right font-medium text-ink tabular-nums">
                {{ formatRupiah(item.line_total) }}
              </td>
            </tr>
          </tbody>
        </table>
      </Card>

      <Card title="Ringkasan">
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between gap-3">
            <dt class="text-ink-muted">Status</dt>
            <dd>
              <Badge :tone="sale.status === 'void' ? 'danger' : 'success'">
                {{ sale.status === 'void' ? 'Dibatalkan' : 'Selesai' }}
              </Badge>
            </dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-ink-muted">Metode bayar</dt>
            <dd class="text-ink">{{ METHOD_LABELS[sale.payment_method] ?? sale.payment_method }}</dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-ink-muted">Subtotal</dt>
            <dd class="text-ink tabular-nums">{{ formatRupiah(sale.subtotal) }}</dd>
          </div>
          <div v-if="sale.discount" class="flex justify-between gap-3">
            <dt class="text-ink-muted">Diskon</dt>
            <dd class="text-ink tabular-nums">−{{ formatRupiah(sale.discount) }}</dd>
          </div>
          <div v-if="sale.tax" class="flex justify-between gap-3">
            <dt class="text-ink-muted">Pajak</dt>
            <dd class="text-ink tabular-nums">{{ formatRupiah(sale.tax) }}</dd>
          </div>
          <div class="flex justify-between gap-3 border-t border-border pt-2">
            <dt class="font-medium text-ink">Total</dt>
            <dd class="text-base font-semibold text-ink tabular-nums">{{ formatRupiah(sale.total) }}</dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-ink-muted">Dibayar</dt>
            <dd class="text-ink tabular-nums">{{ formatRupiah(sale.paid) }}</dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-ink-muted">Kembalian</dt>
            <dd class="text-ink tabular-nums">{{ formatRupiah(sale.change_due) }}</dd>
          </div>
        </dl>
      </Card>
    </div>

    <Modal v-model:open="confirming" title="Batalkan transaksi?">
      <p class="text-sm text-ink-muted">
        Struk <span class="font-medium text-ink">{{ sale.number }}</span> ditandai dibatalkan,
        stok produk dikembalikan, dan buku kas dikoreksi dengan entri lawan sebesar
        {{ formatRupiah(sale.total) }}. Baris kas aslinya sengaja tidak dihapus supaya rekonsiliasi
        sesi kasir tetap bisa dijelaskan.
      </p>
      <template #footer>
        <Button variant="ghost" size="sm" @click="confirming = false">Tidak jadi</Button>
        <Button variant="danger" size="sm" @click="voidSale">Ya, batalkan</Button>
      </template>
    </Modal>
  </AppLayout>
</template>
