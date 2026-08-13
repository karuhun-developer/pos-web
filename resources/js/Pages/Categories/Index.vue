<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { Boxes, Pencil, Plus, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import { formatNumber } from '@/lib/utils'
import type { SharedProps } from '@/types'

interface CategoryRow {
  id: string
  name: string
  color: string | null
  sort_order: number
  products_count: number
}

const props = defineProps<{
  categories: CategoryRow[]
  uncategorized_count: number
}>()

const page = usePage<SharedProps>()
const canManage = computed(() => page.props.auth.user?.permissions.includes('catalog.manage') ?? false)

// Warna kategori dipakai sebagai penanda identitas di kasir, jadi pilihannya
// dibatasi ke palet yang sudah divalidasi kontrasnya — bukan color picker bebas.
const SWATCHES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948']

const editing = ref<CategoryRow | null>(null)
const open = ref(false)
const confirming = ref<CategoryRow | null>(null)

const form = useForm({ name: '', color: null as string | null, sort_order: 0 })

function openCreate() {
  editing.value = null
  form.defaults({ name: '', color: null, sort_order: props.categories.length })
  form.reset()
  form.clearErrors()
  open.value = true
}

function openEdit(category: CategoryRow) {
  editing.value = category
  form.defaults({ name: category.name, color: category.color, sort_order: category.sort_order })
  form.reset()
  form.clearErrors()
  open.value = true
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => (open.value = false) }

  if (editing.value) {
    form.put(route('categories.update', editing.value.id), options)

    return
  }

  form.post(route('categories.store'), options)
}

function destroy() {
  if (!confirming.value) return
  router.delete(route('categories.destroy', confirming.value.id), {
    preserveScroll: true,
    onFinish: () => (confirming.value = null),
  })
}
</script>

<template>
  <AppLayout title="Kategori" subtitle="Pengelompokan produk di layar kasir">
    <template #actions>
      <Button v-if="canManage" size="sm" @click="openCreate">
        <Plus class="size-4" />
        Kategori baru
      </Button>
    </template>

    <Card flush>
      <EmptyState
        v-if="!categories.length"
        :icon="Boxes"
        title="Belum ada kategori"
        description="Kategori membantu kasir menemukan produk lebih cepat."
      >
        <Button v-if="canManage" size="sm" @click="openCreate">Tambah kategori</Button>
      </EmptyState>

      <ul v-else class="divide-y divide-border">
        <li
          v-for="category in categories"
          :key="category.id"
          class="flex items-center gap-3 px-5 py-3"
        >
          <span
            class="size-3 shrink-0 rounded-full"
            :style="{ backgroundColor: category.color ?? 'var(--color-border-strong)' }"
            aria-hidden="true"
          />
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-ink">{{ category.name }}</p>
            <p class="text-xs text-ink-subtle">{{ formatNumber(category.products_count) }} produk</p>
          </div>
          <div v-if="canManage" class="flex shrink-0 items-center gap-1">
            <Button variant="ghost" size="icon" class="size-8" aria-label="Ubah" @click="openEdit(category)">
              <Pencil class="size-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="size-8"
              aria-label="Hapus"
              @click="confirming = category"
            >
              <Trash2 class="size-4" />
            </Button>
          </div>
        </li>
      </ul>

      <p v-if="uncategorized_count" class="border-t border-border px-5 py-3 text-xs text-ink-subtle">
        {{ formatNumber(uncategorized_count) }} produk belum punya kategori.
      </p>
    </Card>

    <Modal v-model:open="open" :title="editing ? 'Ubah kategori' : 'Kategori baru'">
      <form id="category-form" class="space-y-4" @submit.prevent="submit">
        <FormField label="Nama" required :error="form.errors.name">
          <Input v-model="form.name" :invalid="!!form.errors.name" placeholder="Minuman" />
        </FormField>

        <FormField label="Warna" :error="form.errors.color" hint="Opsional — penanda di layar kasir.">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="swatch in SWATCHES"
              :key="swatch"
              type="button"
              class="size-8 rounded-lg border-2 transition"
              :class="form.color === swatch ? 'border-ink' : 'border-transparent'"
              :style="{ backgroundColor: swatch }"
              :aria-label="`Warna ${swatch}`"
              :aria-pressed="form.color === swatch"
              @click="form.color = swatch"
            />
            <button
              type="button"
              class="h-8 rounded-lg border border-border-strong px-3 text-xs text-ink-muted transition hover:bg-surface-sunken"
              @click="form.color = null"
            >
              Tanpa warna
            </button>
          </div>
        </FormField>

        <FormField label="Urutan" :error="form.errors.sort_order" hint="Angka kecil tampil lebih dulu.">
          <Input v-model.number="form.sort_order" type="number" min="0" />
        </FormField>
      </form>

      <template #footer>
        <Button variant="ghost" size="sm" @click="open = false">Batal</Button>
        <Button type="submit" form="category-form" size="sm" :disabled="form.processing">Simpan</Button>
      </template>
    </Modal>

    <Modal :open="confirming !== null" title="Hapus kategori?" @update:open="confirming = null">
      <p class="text-sm text-ink-muted">
        <span class="font-medium text-ink">{{ confirming?.name }}</span> akan dihapus. Produk di
        dalamnya tidak ikut terhapus — mereka jadi "Tanpa kategori".
      </p>
      <template #footer>
        <Button variant="ghost" size="sm" @click="confirming = null">Batal</Button>
        <Button variant="danger" size="sm" @click="destroy">Hapus</Button>
      </template>
    </Modal>
  </AppLayout>
</template>
