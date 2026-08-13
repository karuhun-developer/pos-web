<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, ImageOff, ScanLine, Upload, X } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarcodeScanner from '@/Components/BarcodeScanner.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'
import MoneyInput from '@/Components/ui/MoneyInput.vue'
import { isScanSupported, toSymbology } from '@/lib/barcode'
import type { Category, Product } from '@/types'

const props = defineProps<{
  product: Product | null
  categories: Category[]
}>()

const editing = computed(() => props.product !== null)

const BARCODE_TYPES = ['EAN13', 'EAN8', 'UPC', 'CODE128', 'CODE39', 'ITF14']

const form = useForm({
  name: props.product?.name ?? '',
  category_id: props.product?.category_id ?? '',
  sku: props.product?.sku ?? '',
  barcode: props.product?.barcode ?? '',
  barcode_type: props.product?.barcode_type ?? 'EAN13',
  price: props.product?.price ?? 0,
  cost: props.product?.cost ?? 0,
  track_stock: Boolean(props.product?.track_stock ?? 0),
  stock: props.product?.stock ?? 0,
  active: Boolean(props.product?.active ?? 1),
  image: null as File | null,
  remove_image: false,
})

const preview = ref<string | null>(props.product?.image_url ?? null)

const scanning = ref(false)
const scanSupported = isScanSupported()

function fillBarcode(value: string, format: string) {
  form.barcode = value

  const symbology = toSymbology(format)

  // Simbologi cuma ditimpa kalau dekodernya mengenalinya; QR (dan format lain
  // di luar daftar) dibiarkan apa adanya supaya pilihan yang sudah ada tidak hilang.
  if (symbology && BARCODE_TYPES.includes(symbology)) form.barcode_type = symbology
}

function pickImage(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0] ?? null
  form.image = file
  form.remove_image = false
  preview.value = file ? URL.createObjectURL(file) : null
}

function clearImage() {
  form.image = null
  form.remove_image = true
  preview.value = null
}

function submit() {
  // Ada unggahan file, jadi multipart; PUT dikirim lewat _method spoofing
  // (transform) karena PHP tidak mem-parse body multipart pada PUT.
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(
      route('products.update', props.product!.id),
      { forceFormData: true },
    )

    return
  }

  form.post(route('products.store'), { forceFormData: true })
}
</script>

<template>
  <AppLayout :title="editing ? 'Ubah produk' : 'Produk baru'">
    <template #actions>
      <Link :href="route('products.index')">
        <Button variant="ghost" size="sm">
          <ArrowLeft class="size-4" />
          Kembali
        </Button>
      </Link>
    </template>

    <form class="grid gap-4 lg:grid-cols-3" @submit.prevent="submit">
      <div class="space-y-4 lg:col-span-2">
        <Card title="Informasi produk">
          <div class="grid gap-4 sm:grid-cols-2">
            <FormField label="Nama" required :error="form.errors.name" class="sm:col-span-2">
              <Input v-model="form.name" :invalid="!!form.errors.name" placeholder="Kopi susu gula aren" />
            </FormField>

            <FormField label="Kategori" :error="form.errors.category_id">
              <select
                v-model="form.category_id"
                class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm
                       text-ink focus:border-brand focus:outline-none"
              >
                <option value="">Tanpa kategori</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </FormField>

            <FormField label="SKU" :error="form.errors.sku" hint="Kode internal, harus unik di toko ini.">
              <Input v-model="form.sku" :invalid="!!form.errors.sku" placeholder="KOP-001" />
            </FormField>

            <FormField label="Barcode" :error="form.errors.barcode">
              <div class="flex gap-2">
                <Input
                  v-model="form.barcode"
                  :invalid="!!form.errors.barcode"
                  placeholder="8991234567890"
                  class="flex-1"
                />
                <Button
                  v-if="scanSupported"
                  variant="outline"
                  size="icon"
                  aria-label="Pindai barcode dengan kamera"
                  title="Pindai barcode"
                  @click="scanning = true"
                >
                  <ScanLine class="size-4" />
                </Button>
              </div>
            </FormField>

            <FormField label="Simbologi barcode" :error="form.errors.barcode_type">
              <select
                v-model="form.barcode_type"
                class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm
                       text-ink focus:border-brand focus:outline-none"
              >
                <option v-for="type in BARCODE_TYPES" :key="type" :value="type">{{ type }}</option>
              </select>
            </FormField>
          </div>
        </Card>

        <Card title="Harga & stok">
          <div class="grid gap-4 sm:grid-cols-2">
            <FormField label="Harga jual" required :error="form.errors.price" hint="Rupiah, tanpa desimal.">
              <MoneyInput v-model="form.price" :invalid="!!form.errors.price" />
            </FormField>

            <FormField label="Harga modal" :error="form.errors.cost" hint="Dipakai menghitung margin di laporan.">
              <MoneyInput v-model="form.cost" :invalid="!!form.errors.cost" />
            </FormField>

            <label class="flex items-center gap-3 rounded-xl border border-border p-3 sm:col-span-2">
              <input v-model="form.track_stock" type="checkbox" class="size-4 accent-brand" />
              <span class="min-w-0">
                <span class="block text-sm font-medium text-ink">Lacak stok</span>
                <span class="block text-xs text-ink-muted">
                  Stok berkurang otomatis setiap penjualan di kasir.
                </span>
              </span>
            </label>

            <FormField v-if="form.track_stock" label="Stok saat ini" :error="form.errors.stock">
              <Input v-model.number="form.stock" type="number" :invalid="!!form.errors.stock" />
            </FormField>
          </div>
        </Card>
      </div>

      <div class="space-y-4">
        <Card title="Gambar">
          <div class="space-y-3">
            <div
              class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-xl
                     border border-dashed border-border-strong bg-surface-sunken"
            >
              <img v-if="preview" :src="preview" alt="Pratinjau gambar produk" class="size-full object-cover" />
              <ImageOff v-else class="size-8 text-ink-subtle" />
            </div>

            <div class="flex gap-2">
              <label class="flex-1">
                <input type="file" accept="image/*" class="sr-only" @change="pickImage" />
                <span
                  class="inline-flex h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-xl
                         border border-border-strong bg-surface-raised text-sm font-medium text-ink
                         transition hover:bg-surface-sunken"
                >
                  <Upload class="size-4" />
                  Pilih gambar
                </span>
              </label>
              <Button v-if="preview" variant="ghost" size="icon" aria-label="Hapus gambar" @click="clearImage">
                <X class="size-4" />
              </Button>
            </div>

            <p v-if="form.errors.image" class="text-xs text-danger">{{ form.errors.image }}</p>
            <p v-else class="text-xs text-ink-subtle">
              Maksimal 4 MB. Gambar ikut tersinkron ke aplikasi kasir.
            </p>
          </div>
        </Card>

        <Card title="Status">
          <label class="flex items-center gap-3">
            <input v-model="form.active" type="checkbox" class="size-4 accent-brand" />
            <span class="min-w-0">
              <span class="block text-sm font-medium text-ink">Aktif</span>
              <span class="block text-xs text-ink-muted">Produk nonaktif tidak muncul di kasir.</span>
            </span>
          </label>
        </Card>

        <div class="flex items-center gap-2">
          <Button type="submit" :disabled="form.processing" class="flex-1">
            {{ editing ? 'Simpan perubahan' : 'Simpan produk' }}
          </Button>
          <Link :href="route('products.index')">
            <Button variant="ghost">Batal</Button>
          </Link>
        </div>
      </div>
    </form>

    <BarcodeScanner v-model:open="scanning" @detected="fillBarcode" />
  </AppLayout>
</template>
