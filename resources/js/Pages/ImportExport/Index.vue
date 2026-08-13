<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { CircleAlert, Download, FileDown, FileSpreadsheet, TriangleAlert, Upload } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import FormField from '@/Components/ui/FormField.vue'
import PeriodFilter from '@/Components/reports/PeriodFilter.vue'
import { formatNumber } from '@/lib/utils'
import type { ReportPeriod } from '@/types/reports'

interface DatasetInfo {
  value: string
  label: string
  description: string
  uses_period: boolean
  importable: boolean
  can_import: boolean
  /** header => keterangan; null untuk dataset yang hanya bisa diekspor. */
  columns: Record<string, string> | null
}

interface PreviewRow {
  line: number
  status: 'new' | 'update' | 'error'
  label: string
  reason: string | null
}

interface Preview {
  token: string
  dataset: string
  filename: string
  summary: { total: number; new: number; update: number; error: number }
  rows: PreviewRow[]
  /** Baris yang tidak ikut ditampilkan (di luar 200 pertama). */
  truncated: number
}

const props = defineProps<{
  period: ReportPeriod
  formats: string[]
  can_export: boolean
  datasets: DatasetInfo[]
  preview: Preview | null
}>()

const importable = computed(() => props.datasets.filter((dataset) => dataset.can_import))

const form = useForm<{ dataset: string; berkas: File | null }>({
  dataset: importable.value[0]?.value ?? '',
  berkas: null,
})

const fileInput = ref<HTMLInputElement | null>(null)
const selected = computed(() => importable.value.find((d) => d.value === form.dataset) ?? null)

// Pratinjau datang lewat flash session, jadi "batal" cukup disembunyikan di
// sini — berkasnya sendiri dibersihkan otomatis setelah 24 jam.
const dismissed = ref(false)
const preview = computed(() => (dismissed.value ? null : props.preview))
const onlyErrors = ref(false)

const visibleRows = computed(() =>
  onlyErrors.value
    ? (preview.value?.rows ?? []).filter((row) => row.status === 'error')
    : (preview.value?.rows ?? []),
)

const previewDataset = computed(
  () => props.datasets.find((d) => d.value === preview.value?.dataset) ?? null,
)

const applicable = computed(() =>
  preview.value ? preview.value.summary.new + preview.value.summary.update : 0,
)

const commit = useForm({ dataset: '', token: '' })

/** Rentang ikut ke tautan unduhan hanya untuk dataset yang memang terikat waktu. */
function exportParams(dataset: DatasetInfo, format: string): Record<string, string> {
  const params: Record<string, string> = { dataset: dataset.value, format }

  if (dataset.uses_period) {
    params.preset = props.period.preset
    params.from = props.period.from
    params.to = props.period.to
  }

  return params
}

function pickFile(event: Event) {
  form.berkas = (event.target as HTMLInputElement).files?.[0] ?? null
}

function submitPreview() {
  form.post(route('io.preview'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      dismissed.value = false
      onlyErrors.value = false
      form.reset('berkas')
      if (fileInput.value) fileInput.value.value = ''
    },
  })
}

function apply() {
  if (!preview.value) return

  commit.dataset = preview.value.dataset
  commit.token = preview.value.token
  commit.post(route('io.commit'), { preserveScroll: true })
}

function cancel() {
  dismissed.value = true
}

const STATUS = {
  new: { label: 'Baru', tone: 'success' },
  update: { label: 'Diperbarui', tone: 'brand' },
  error: { label: 'Error', tone: 'danger' },
} as const
</script>

<template>
  <AppLayout title="Impor & Ekspor" subtitle="Unduh data toko atau unggah perubahan massal">
    <template #actions>
      <PeriodFilter :period="period" route-name="io.index" />
    </template>

    <div class="space-y-6">
      <!-- Pratinjau tampil paling atas: begitu berkas diunggah, inilah yang
           sedang dikerjakan user. -->
      <Card v-if="preview" :title="`Pratinjau — ${preview.filename}`" flush>
        <template #actions>
          <Button variant="ghost" size="sm" @click="cancel">Batal</Button>
          <Button size="sm" :disabled="!applicable || commit.processing" @click="apply">
            <Upload class="size-4" />
            Terapkan {{ formatNumber(applicable) }} baris
          </Button>
        </template>

        <div class="grid grid-cols-2 gap-px border-b border-border bg-border sm:grid-cols-4">
          <div class="bg-surface-raised px-5 py-4">
            <p class="text-xs text-ink-muted">Total baris</p>
            <p class="mt-1 text-xl font-semibold text-ink">
              {{ formatNumber(preview.summary.total) }}
            </p>
          </div>
          <div class="bg-surface-raised px-5 py-4">
            <p class="text-xs text-ink-muted">Baru</p>
            <p class="mt-1 text-xl font-semibold text-ink">
              {{ formatNumber(preview.summary.new) }}
            </p>
          </div>
          <div class="bg-surface-raised px-5 py-4">
            <p class="text-xs text-ink-muted">Diperbarui</p>
            <p class="mt-1 text-xl font-semibold text-ink">
              {{ formatNumber(preview.summary.update) }}
            </p>
          </div>
          <div class="bg-surface-raised px-5 py-4">
            <p class="text-xs text-ink-muted">Error</p>
            <p
              class="mt-1 text-xl font-semibold"
              :class="preview.summary.error ? 'text-danger' : 'text-ink'"
            >
              {{ formatNumber(preview.summary.error) }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-3">
          <p class="text-xs text-ink-muted">
            <span class="font-medium text-ink">{{ previewDataset?.label ?? preview.dataset }}</span>
            — belum ada yang tersimpan. Baris error dilewati saat diterapkan, sisanya tetap masuk.
          </p>
          <label class="flex items-center gap-2 text-xs text-ink-muted">
            <input
              v-model="onlyErrors"
              type="checkbox"
              class="size-4 rounded border-border-strong text-brand"
            />
            Hanya tampilkan error
          </label>
        </div>

        <div class="max-h-[28rem] overflow-auto">
          <table class="w-full border-collapse text-sm">
            <thead class="sticky top-0 bg-surface-raised">
              <tr class="border-b border-border">
                <th scope="col" class="px-5 py-3 text-left text-xs font-medium tracking-wide text-ink-muted uppercase">
                  Baris
                </th>
                <th scope="col" class="px-5 py-3 text-left text-xs font-medium tracking-wide text-ink-muted uppercase">
                  Status
                </th>
                <th scope="col" class="px-5 py-3 text-left text-xs font-medium tracking-wide text-ink-muted uppercase">
                  Data
                </th>
                <th scope="col" class="px-5 py-3 text-left text-xs font-medium tracking-wide text-ink-muted uppercase">
                  Catatan
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in visibleRows"
                :key="row.line"
                class="border-b border-border last:border-0"
              >
                <td class="px-5 py-2.5 text-xs text-ink-subtle tabular-nums">{{ row.line }}</td>
                <td class="px-5 py-2.5">
                  <Badge :tone="STATUS[row.status].tone">
                    <CircleAlert v-if="row.status === 'error'" class="size-3" />
                    {{ STATUS[row.status].label }}
                  </Badge>
                </td>
                <td class="px-5 py-2.5 text-ink">{{ row.label }}</td>
                <td class="px-5 py-2.5 text-xs" :class="row.reason ? 'text-danger' : 'text-ink-subtle'">
                  {{ row.reason ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>

          <p v-if="!visibleRows.length" class="px-5 py-8 text-center text-sm text-ink-muted">
            Tidak ada baris error. Semuanya siap diterapkan.
          </p>
        </div>

        <p v-if="preview.truncated" class="border-t border-border px-5 py-3 text-xs text-ink-subtle">
          {{ formatNumber(preview.truncated) }} baris lain tidak ditampilkan, tapi tetap ikut
          diterapkan.
        </p>
      </Card>

      <!-- Impor -->
      <Card
        title="Impor data"
        description="Unggah berkas, periksa hasilnya baris per baris, baru diterapkan."
      >
        <EmptyState
          v-if="!importable.length"
          :icon="Upload"
          title="Tidak ada data yang bisa diimpor"
          description="Impor butuh izin mengelola katalog atau arus kas di toko ini."
        />

        <form v-else class="space-y-4" @submit.prevent="submitPreview">
          <div class="grid gap-4 sm:grid-cols-2">
            <FormField label="Jenis data" required :error="form.errors.dataset">
              <select
                v-model="form.dataset"
                class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
              >
                <option v-for="dataset in importable" :key="dataset.value" :value="dataset.value">
                  {{ dataset.label }}
                </option>
              </select>
            </FormField>

            <FormField
              label="Berkas"
              required
              :error="form.errors.berkas"
              hint="CSV atau XLSX, maksimal 5 MB."
            >
              <input
                ref="fileInput"
                type="file"
                accept=".csv,.xlsx,text/csv"
                class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink file:mr-3 file:h-full file:border-0 file:bg-transparent file:text-xs file:text-brand focus:border-brand focus:outline-none"
                @change="pickFile"
              />
            </FormField>
          </div>

          <div v-if="selected?.columns" class="rounded-xl border border-border bg-surface p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-xs font-medium text-ink">Kolom yang dikenali</p>
              <div class="flex items-center gap-1">
                <a
                  v-for="format in formats"
                  :key="format"
                  :href="route('io.template', { dataset: selected.value, format })"
                  class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-brand hover:bg-brand-soft"
                >
                  <FileDown class="size-3.5" />
                  Template {{ format.toUpperCase() }}
                </a>
              </div>
            </div>
            <dl class="mt-3 grid gap-x-6 gap-y-1.5 sm:grid-cols-2">
              <div v-for="(hint, column) in selected.columns" :key="column" class="text-xs">
                <dt class="inline font-medium text-ink">{{ column }}</dt>
                <dd class="inline text-ink-muted"> — {{ hint }}</dd>
              </div>
            </dl>
            <p class="mt-3 text-xs text-ink-subtle">
              Urutan kolom bebas dan kolom yang tidak dikenal diabaikan. Sel yang dikosongkan pada
              baris yang cocok tidak menimpa nilai lama.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <Button type="submit" size="sm" :disabled="!form.berkas || form.processing">
              <Upload class="size-4" />
              Pratinjau
            </Button>
            <p class="text-xs text-ink-subtle">Belum ada yang tersimpan sampai kamu konfirmasi.</p>
          </div>
        </form>
      </Card>

      <!-- Ekspor -->
      <Card
        title="Ekspor data"
        :description="`Dataset bertanda rentang mengikuti ${period.from} s/d ${period.to}.`"
        flush
      >
        <EmptyState
          v-if="!can_export"
          :icon="Download"
          title="Ekspor tidak tersedia"
          description="Butuh izin melihat laporan untuk mengunduh data toko."
        />

        <ul v-else class="divide-y divide-border">
          <li
            v-for="dataset in datasets"
            :key="dataset.value"
            class="flex flex-wrap items-center gap-3 px-5 py-3.5"
          >
            <FileSpreadsheet class="size-4 shrink-0 text-ink-subtle" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="flex items-center gap-2 text-sm font-medium text-ink">
                {{ dataset.label }}
                <Badge v-if="dataset.uses_period" tone="neutral">{{ period.days }} hari</Badge>
              </p>
              <p class="mt-0.5 text-xs text-ink-muted">{{ dataset.description }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
              <a
                v-for="format in formats"
                :key="format"
                :href="route('io.export', exportParams(dataset, format))"
                class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-border-strong px-2.5 text-xs font-medium text-ink-muted transition hover:bg-surface-sunken hover:text-ink"
              >
                <Download class="size-3.5" />
                {{ format.toUpperCase() }}
              </a>
            </div>
          </li>
        </ul>
      </Card>

      <div class="flex items-start gap-2 text-xs text-ink-subtle">
        <TriangleAlert class="mt-px size-3.5 shrink-0" aria-hidden="true" />
        <p>
          Perubahan dari impor ikut tersinkron: perangkat kasir menariknya pada sinkronisasi
          berikutnya, tidak perlu dimasukkan ulang di sana.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
