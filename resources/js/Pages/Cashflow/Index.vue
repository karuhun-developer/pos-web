<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { Pencil, Plus, Tags, Trash2, Wallet } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import StatTile from '@/Components/StatTile.vue'
import CashflowCategoryManager from '@/Components/CashflowCategoryManager.vue'
import { formatDate, formatRupiah, toDateInput } from '@/lib/utils'
import type { CashflowCategory, CashflowEntry, Paginated, SharedProps } from '@/types'

const props = defineProps<{
  entries: Paginated<CashflowEntry>
  categories: CashflowCategory[]
  filters: { direction: string; category: string | null; source: string; from: string; to: string }
  totals: { income: number; expense: number; net: number }
}>()

const page = usePage<SharedProps>()
const canManage = computed(() => page.props.auth.user?.permissions.includes('cashflow.manage') ?? false)

const direction = ref(props.filters.direction)
const category = ref(props.filters.category ?? '')
const source = ref(props.filters.source)
const from = ref(props.filters.from)
const to = ref(props.filters.to)

const categoryNames = computed(() => new Map(props.categories.map((c) => [c.id, c.name])))

const open = ref(false)
const editing = ref<CashflowEntry | null>(null)
const confirming = ref<CashflowEntry | null>(null)
const managingCategories = ref(false)

const form = useForm({
  category_id: '' as string,
  type: 'expense' as 'income' | 'expense',
  amount: 0,
  note: '',
  occurred_on: props.filters.to,
})

function apply() {
  router.get(
    route('cashflow.index'),
    {
      direction: direction.value,
      category: category.value || undefined,
      source: source.value,
      from: from.value,
      to: to.value,
    },
    { preserveState: true, preserveScroll: true, replace: true },
  )
}

watch([direction, category, source, from, to], apply)

function openCreate() {
  editing.value = null
  form.defaults({ category_id: '', type: 'expense', amount: 0, note: '', occurred_on: props.filters.to })
  form.reset()
  form.clearErrors()
  open.value = true
}

function openEdit(entry: CashflowEntry) {
  editing.value = entry
  form.defaults({
    category_id: entry.category_id ?? '',
    type: entry.direction === 'debit' ? 'income' : 'expense',
    amount: entry.amount,
    note: entry.note ?? '',
    occurred_on: toDateInput(entry.occurred_at),
  })
  form.reset()
  form.clearErrors()
  open.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => (open.value = false) }

  if (editing.value) {
    form.put(route('cashflow.update', editing.value.id), options)

    return
  }

  form.post(route('cashflow.store'), options)
}

function destroy() {
  if (!confirming.value) return
  router.delete(route('cashflow.destroy', confirming.value.id), {
    preserveScroll: true,
    onFinish: () => (confirming.value = null),
  })
}
</script>

<template>
  <AppLayout title="Arus Kas" subtitle="Uang masuk dan keluar di luar transaksi kasir">
    <template #actions>
      <Button v-if="canManage" variant="outline" size="sm" @click="managingCategories = true">
        <Tags class="size-4" />
        Kategori
      </Button>
      <Button v-if="canManage" size="sm" @click="openCreate">
        <Plus class="size-4" />
        Catatan baru
      </Button>
    </template>

    <div class="grid gap-4 sm:grid-cols-3">
      <StatTile label="Uang masuk" :value="formatRupiah(totals.income)" hint="Termasuk penjualan" />
      <StatTile label="Uang keluar" :value="formatRupiah(totals.expense)" hint="Beban & pengeluaran" />
      <StatTile label="Arus bersih" :value="formatRupiah(totals.net)" hint="Masuk − keluar" />
    </div>

    <Card flush class="mt-4">
      <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
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

        <select
          v-model="direction"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter arah"
        >
          <option value="all">Masuk & keluar</option>
          <option value="debit">Hanya masuk</option>
          <option value="credit">Hanya keluar</option>
        </select>

        <select
          v-model="category"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter kategori"
        >
          <option value="">Semua kategori</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>

        <select
          v-model="source"
          class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
          aria-label="Filter sumber"
        >
          <option value="all">Semua sumber</option>
          <option value="manual">Catatan manual</option>
          <option value="sale">Dari penjualan</option>
        </select>
      </div>

      <EmptyState
        v-if="!entries.data.length"
        :icon="Wallet"
        title="Belum ada catatan kas"
        description="Catat pengeluaran seperti belanja bahan, listrik, atau gaji di sini."
      >
        <Button v-if="canManage" size="sm" @click="openCreate">Tambah catatan</Button>
      </EmptyState>

      <ul v-else class="divide-y divide-border">
        <li v-for="entry in entries.data" :key="entry.id" class="flex items-center gap-3 px-5 py-3">
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-ink">
              {{ entry.note || categoryNames.get(entry.category_id ?? '') || 'Tanpa keterangan' }}
            </p>
            <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-ink-subtle">
              <span>{{ formatDate(entry.occurred_at) }}</span>
              <span v-if="entry.category_id">· {{ categoryNames.get(entry.category_id) }}</span>
              <Badge v-if="entry.source === 'sale'" tone="brand">Dari penjualan</Badge>
            </p>
          </div>

          <span
            class="shrink-0 text-sm font-semibold tabular-nums"
            :class="entry.direction === 'debit' ? 'text-success' : 'text-danger'"
          >
            {{ entry.direction === 'debit' ? '+' : '−' }}{{ formatRupiah(entry.amount) }}
          </span>

          <div v-if="canManage && entry.source !== 'sale'" class="flex shrink-0 items-center gap-1">
            <Button variant="ghost" size="icon" class="size-8" aria-label="Ubah" @click="openEdit(entry)">
              <Pencil class="size-4" />
            </Button>
            <Button variant="ghost" size="icon" class="size-8" aria-label="Hapus" @click="confirming = entry">
              <Trash2 class="size-4" />
            </Button>
          </div>
        </li>
      </ul>

      <Pagination :meta="entries" />
    </Card>

    <Modal v-model:open="open" :title="editing ? 'Ubah catatan kas' : 'Catatan kas baru'">
      <form id="cashflow-form" class="space-y-4" @submit.prevent="submit">
        <FormField label="Kategori" :error="form.errors.category_id">
          <select
            v-model="form.category_id"
            class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm
                   text-ink focus:border-brand focus:outline-none"
          >
            <option value="">Tanpa kategori</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">
              {{ c.name }} ({{ c.type === 'income' ? 'masuk' : 'keluar' }})
            </option>
          </select>
        </FormField>

        <FormField
          v-if="!form.category_id"
          label="Tipe"
          required
          :error="form.errors.type"
          hint="Kalau kategori dipilih, arahnya mengikuti kategori."
        >
          <select
            v-model="form.type"
            class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm
                   text-ink focus:border-brand focus:outline-none"
          >
            <option value="income">Uang masuk</option>
            <option value="expense">Uang keluar</option>
          </select>
        </FormField>

        <FormField label="Nominal" required :error="form.errors.amount">
          <Input v-model.number="form.amount" type="number" min="1" :invalid="!!form.errors.amount" />
        </FormField>

        <FormField label="Tanggal" required :error="form.errors.occurred_on">
          <Input v-model="form.occurred_on" type="date" :invalid="!!form.errors.occurred_on" />
        </FormField>

        <FormField label="Catatan" :error="form.errors.note">
          <Input v-model="form.note" placeholder="Belanja bahan di pasar" />
        </FormField>
      </form>

      <template #footer>
        <Button variant="ghost" size="sm" @click="open = false">Batal</Button>
        <Button type="submit" form="cashflow-form" size="sm" :disabled="form.processing">Simpan</Button>
      </template>
    </Modal>

    <Modal :open="confirming !== null" title="Hapus catatan kas?" @update:open="confirming = null">
      <p class="text-sm text-ink-muted">
        Catatan sebesar {{ formatRupiah(confirming?.amount ?? 0) }} akan dihapus dan penghapusannya
        ikut turun ke aplikasi kasir.
      </p>
      <template #footer>
        <Button variant="ghost" size="sm" @click="confirming = null">Batal</Button>
        <Button variant="danger" size="sm" @click="destroy">Hapus</Button>
      </template>
    </Modal>

    <CashflowCategoryManager v-model:open="managingCategories" :categories="categories" />
  </AppLayout>
</template>
