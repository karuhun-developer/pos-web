<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Pencil, Plus, Trash2 } from '@lucide/vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import type { CashflowCategory } from '@/types'

defineProps<{ categories: CashflowCategory[] }>()

const open = defineModel<boolean>('open', { required: true })

const editing = ref<CashflowCategory | null>(null)
const form = useForm({ name: '', type: 'expense' as 'income' | 'expense', sort_order: 0 })

function reset() {
  editing.value = null
  form.defaults({ name: '', type: 'expense', sort_order: 0 })
  form.reset()
  form.clearErrors()
}

function edit(category: CashflowCategory) {
  editing.value = category
  form.defaults({ name: category.name, type: category.type, sort_order: category.sort_order })
  form.reset()
  form.clearErrors()
}

function submit() {
  const options = { preserveScroll: true, onSuccess: () => reset() }

  if (editing.value) {
    form.put(route('cashflow.categories.update', editing.value.id), options)

    return
  }

  form.post(route('cashflow.categories.store'), options)
}

function destroy(category: CashflowCategory) {
  router.delete(route('cashflow.categories.destroy', category.id), { preserveScroll: true })
}
</script>

<template>
  <Modal v-model:open="open" title="Kategori arus kas" size="lg">
    <div class="grid gap-5 sm:grid-cols-2">
      <ul class="divide-y divide-border rounded-xl border border-border">
        <li v-for="category in categories" :key="category.id" class="flex items-center gap-2 px-3 py-2">
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm text-ink">{{ category.name }}</p>
            <p class="text-xs text-ink-subtle">
              {{ category.type === 'income' ? 'Uang masuk' : 'Uang keluar' }}
            </p>
          </div>
          <!-- Kategori bawaan dipakai ledger checkout; tipenya dikunci dan tidak bisa dihapus. -->
          <Badge v-if="category.is_system" tone="neutral">Bawaan</Badge>
          <Button variant="ghost" size="icon" class="size-8" aria-label="Ubah" @click="edit(category)">
            <Pencil class="size-4" />
          </Button>
          <Button
            v-if="!category.is_system"
            variant="ghost"
            size="icon"
            class="size-8"
            aria-label="Hapus"
            @click="destroy(category)"
          >
            <Trash2 class="size-4" />
          </Button>
        </li>
        <li v-if="!categories.length" class="px-3 py-6 text-center text-xs text-ink-subtle">
          Belum ada kategori.
        </li>
      </ul>

      <form class="space-y-3" @submit.prevent="submit">
        <FormField label="Nama" required :error="form.errors.name">
          <Input v-model="form.name" :invalid="!!form.errors.name" placeholder="Belanja bahan" />
        </FormField>

        <FormField label="Tipe" required :error="form.errors.type">
          <select
            v-model="form.type"
            :disabled="Boolean(editing?.is_system)"
            class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm
                   text-ink focus:border-brand focus:outline-none disabled:opacity-50"
          >
            <option value="income">Uang masuk</option>
            <option value="expense">Uang keluar</option>
          </select>
        </FormField>

        <FormField label="Urutan" :error="form.errors.sort_order">
          <Input v-model.number="form.sort_order" type="number" min="0" />
        </FormField>

        <div class="flex items-center gap-2 pt-1">
          <Button type="submit" size="sm" :disabled="form.processing">
            <Plus v-if="!editing" class="size-4" />
            {{ editing ? 'Simpan perubahan' : 'Tambah kategori' }}
          </Button>
          <Button v-if="editing" variant="ghost" size="sm" @click="reset">Batal</Button>
        </div>
      </form>
    </div>
  </Modal>
</template>
