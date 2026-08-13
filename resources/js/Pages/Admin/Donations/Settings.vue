<script setup lang="ts">
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Plus, QrCode, Trash2 } from '@lucide/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'

interface BankAccount {
  bank: string
  account_number: string
  account_name: string
}

const props = defineProps<{
  settings: {
    qris_path: string | null
    qris_url: string | null
    banks: BankAccount[]
    saweria_url: string | null
    note: string | null
  }
}>()

const form = useForm<{
  qris: File | null
  remove_qris: boolean
  banks: BankAccount[]
  saweria_url: string
  note: string
}>({
  qris: null,
  remove_qris: false,
  // Satu baris kosong supaya bisa langsung diisi tanpa mengklik "tambah" dulu.
  banks: props.settings.banks.length ? [...props.settings.banks] : [blank()],
  saweria_url: props.settings.saweria_url ?? '',
  note: props.settings.note ?? '',
})

/** Pratinjau QRIS: berkas yang baru dipilih dulu, baru yang tersimpan. */
const preview = ref<string | null>(props.settings.qris_url)

function blank(): BankAccount {
  return { bank: '', account_number: '', account_name: '' }
}

function pickQris(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0] ?? null

  form.qris = file
  form.remove_qris = false
  preview.value = file ? URL.createObjectURL(file) : props.settings.qris_url
}

function dropQris() {
  form.qris = null
  form.remove_qris = true
  preview.value = null
}

function submit() {
  // forceFormData: ada unggahan berkas, jadi payloadnya harus multipart.
  form.post(route('admin.donations.settings.update'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.qris = null
      form.remove_qris = false
    },
  })
}
</script>

<template>
  <AdminLayout title="Cara berdonasi" subtitle="Ke mana orang mengirim uangnya">
    <template #actions>
      <Link :href="route('admin.donations.index')">
        <Button variant="outline" size="sm">
          <ArrowLeft class="size-4" />
          Daftar donasi
        </Button>
      </Link>
    </template>

    <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="submit">
      <Card title="QRIS" description="Gambar yang di-scan donatur di halaman /dukung">
        <div class="flex items-start gap-4">
          <div
            class="flex size-32 shrink-0 items-center justify-center overflow-hidden rounded-xl border
                   border-dashed border-border-strong bg-surface-sunken"
          >
            <img v-if="preview" :src="preview" alt="Pratinjau QRIS" class="size-full object-contain" />
            <QrCode v-else class="size-8 text-ink-subtle" aria-hidden="true" />
          </div>

          <div class="min-w-0 flex-1 space-y-2">
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              class="block w-full text-sm text-ink-muted file:mr-3 file:rounded-xl file:border file:border-border-strong
                     file:bg-surface-raised file:px-3 file:py-1.5 file:text-sm file:text-ink"
              @change="pickQris"
            />
            <p v-if="form.errors.qris" class="text-xs text-danger">{{ form.errors.qris }}</p>
            <p v-else class="text-xs text-ink-subtle">PNG/JPG/WebP, maksimal 2 MB.</p>

            <button
              v-if="preview"
              type="button"
              class="inline-flex items-center gap-1.5 text-xs font-medium text-danger"
              @click="dropQris"
            >
              <Trash2 class="size-3.5" />
              Hapus gambar
            </button>
          </div>
        </div>
      </Card>

      <Card title="Saweria" description="Tombol keluar untuk yang lebih suka lewat platform">
        <div class="space-y-4">
          <FormField label="Tautan Saweria" :error="form.errors.saweria_url" hint="Kosongkan kalau tidak dipakai.">
            <Input
              v-model="form.saweria_url"
              type="url"
              placeholder="https://saweria.co/namamu"
              :invalid="!!form.errors.saweria_url"
            />
          </FormField>

          <FormField
            label="Catatan di halaman donasi"
            :error="form.errors.note"
            hint="Satu kalimat, tampil di atas cara pembayaran."
          >
            <textarea
              v-model="form.note"
              rows="3"
              maxlength="300"
              class="w-full rounded-xl border border-border-strong bg-surface-raised px-3 py-2 text-sm
                     text-ink focus:border-brand focus:outline-none"
              placeholder="Donasi dipakai untuk biaya server. Terima kasih!"
            />
          </FormField>
        </div>
      </Card>

      <Card
        class="lg:col-span-2"
        title="Rekening bank"
        description="Maksimal 5. Baris yang bank atau nomornya kosong tidak disimpan."
      >
        <div class="space-y-3">
          <div
            v-for="(bank, index) in form.banks"
            :key="index"
            class="grid gap-3 sm:grid-cols-[1fr_1.2fr_1.4fr_auto]"
          >
            <Input v-model="bank.bank" placeholder="BCA" aria-label="Nama bank" />
            <Input v-model="bank.account_number" placeholder="1234567890" aria-label="Nomor rekening" />
            <Input v-model="bank.account_name" placeholder="Atas nama" aria-label="Atas nama" />
            <button
              type="button"
              class="flex h-10 items-center justify-center rounded-xl border border-border-strong px-3
                     text-ink-muted transition hover:text-danger"
              :aria-label="`Hapus rekening ${index + 1}`"
              @click="form.banks.splice(index, 1)"
            >
              <Trash2 class="size-4" />
            </button>
          </div>

          <p v-if="!form.banks.length" class="text-sm text-ink-muted">Belum ada rekening.</p>

          <Button
            v-if="form.banks.length < 5"
            type="button"
            variant="outline"
            size="sm"
            @click="form.banks.push(blank())"
          >
            <Plus class="size-4" />
            Tambah rekening
          </Button>
        </div>
      </Card>

      <div class="lg:col-span-2">
        <Button type="submit" :disabled="form.processing">
          {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
        </Button>
      </div>
    </form>
  </AdminLayout>
</template>
