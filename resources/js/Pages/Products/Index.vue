<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { ImageOff, PackagePlus, PackageSearch, Pencil, Search, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Input from '@/Components/ui/Input.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Modal from '@/Components/ui/Modal.vue'
import { formatNumber, formatRupiah } from '@/lib/utils'
import type { Category, Paginated, Product, SharedProps } from '@/types'

const props = defineProps<{
  products: Paginated<Product>
  categories: Category[]
  filters: { q: string; category: string | null; status: string }
}>()

const page = usePage<SharedProps>()
const canManage = computed(() => page.props.auth.user?.permissions.includes('catalog.manage') ?? false)

const search = ref(props.filters.q)
const category = ref(props.filters.category ?? '')
const status = ref(props.filters.status)
const confirming = ref<Product | null>(null)

const categoryNames = computed(
  () => new Map(props.categories.map((c) => [c.id, c.name])),
)

function apply() {
  router.get(
    route('products.index'),
    { q: search.value || undefined, category: category.value || undefined, status: status.value },
    { preserveState: true, preserveScroll: true, replace: true },
  )
}

// Ketik → tunggu jeda; filter dropdown → langsung.
watchDebounced(search, apply, { debounce: 350 })
watch([category, status], apply)

function destroy() {
  if (!confirming.value) return
  router.delete(route('products.destroy', confirming.value.id), {
    preserveScroll: true,
    onFinish: () => (confirming.value = null),
  })
}
</script>

<template>
  <AppLayout title="Produk" :subtitle="`${formatNumber(products.total)} produk`">
    <template #actions>
      <Link v-if="canManage" :href="route('products.create')">
        <Button size="sm">
          <PackagePlus class="size-4" />
          Produk baru
        </Button>
      </Link>
    </template>

    <Card flush>
      <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
        <div class="relative min-w-56 flex-1">
          <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-subtle" />
          <input
            v-model="search"
            type="search"
            placeholder="Cari nama, SKU, atau barcode…"
            class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised pr-3 pl-9 text-sm
                   text-ink placeholder:text-ink-subtle focus:border-brand focus:outline-none"
          />
        </div>

        <select
          v-model="category"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter kategori"
        >
          <option value="">Semua kategori</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>

        <select
          v-model="status"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter status"
        >
          <option value="all">Semua status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select>
      </div>

      <EmptyState
        v-if="!products.data.length"
        :icon="PackageSearch"
        title="Belum ada produk"
        description="Tambahkan produk pertama, atau impor massal dari CSV/XLSX di halaman Impor/Ekspor."
      >
        <Link v-if="canManage" :href="route('products.create')">
          <Button size="sm">Tambah produk</Button>
        </Link>
      </EmptyState>

      <ul v-else class="grid gap-px bg-border sm:grid-cols-2 xl:grid-cols-3">
        <li
          v-for="product in products.data"
          :key="product.id"
          class="flex gap-3 bg-surface-raised p-4"
        >
          <img
            v-if="product.image_url"
            :src="product.image_url"
            :alt="product.name"
            class="size-16 shrink-0 rounded-xl object-cover"
          />
          <div
            v-else
            class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-surface-sunken text-ink-subtle"
          >
            <ImageOff class="size-5" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex items-start gap-2">
              <p class="min-w-0 flex-1 truncate text-sm font-medium text-ink">{{ product.name }}</p>
              <Badge v-if="!product.active" tone="neutral">Nonaktif</Badge>
            </div>
            <p class="mt-0.5 truncate text-xs text-ink-subtle">
              {{ categoryNames.get(product.category_id ?? '') ?? 'Tanpa kategori' }}
              <template v-if="product.sku"> · {{ product.sku }}</template>
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <span class="text-sm font-semibold text-ink">{{ formatRupiah(product.price) }}</span>
              <Badge v-if="product.track_stock" :tone="product.stock > 0 ? 'success' : 'danger'">
                Stok {{ formatNumber(product.stock) }}
              </Badge>
            </div>
          </div>

          <div v-if="canManage" class="flex shrink-0 items-start gap-1">
            <Link :href="route('products.edit', product.id)" aria-label="Ubah produk">
              <Button variant="ghost" size="icon" class="size-8">
                <Pencil class="size-4" />
              </Button>
            </Link>
            <Button
              variant="ghost"
              size="icon"
              class="size-8"
              aria-label="Hapus produk"
              @click="confirming = product"
            >
              <Trash2 class="size-4" />
            </Button>
          </div>
        </li>
      </ul>

      <Pagination :meta="products" />
    </Card>

    <Modal :open="confirming !== null" title="Hapus produk?" @update:open="confirming = null">
      <p class="text-sm text-ink-muted">
        <span class="font-medium text-ink">{{ confirming?.name }}</span> akan dihapus dari katalog.
        Transaksi lama tetap menyimpan nama dan harganya, dan penghapusan ini ikut turun ke
        aplikasi kasir saat sinkron berikutnya.
      </p>
      <template #footer>
        <Button variant="ghost" size="sm" @click="confirming = null">Batal</Button>
        <Button variant="danger" size="sm" @click="destroy">Hapus</Button>
      </template>
    </Modal>
  </AppLayout>
</template>
