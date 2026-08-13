<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { Download, Heart, Search } from '@lucide/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import StatTile from '@/Components/StatTile.vue'
import MonthlySeriesPanel from '@/Components/admin/MonthlySeriesPanel.vue'
import { channelLabel, statusLabel } from '@/lib/donation'
import { formatIsoDateTime, formatNumber, formatRupiah } from '@/lib/utils'
import type { Paginated } from '@/types'

interface DonationRow {
  id: number
  order_id: string
  donor_name: string
  donor_email: string | null
  is_anonymous: boolean
  amount: number
  message: string | null
  channel: string
  status: string
  created_at: string | null
  paid_at: string | null
}

const props = defineProps<{
  donations: Paginated<DonationRow>
  filters: { q: string; channel: string; status: string; from: string; to: string }
  options: { channels: string[]; statuses: string[] }
  totals: { count: number; amount: number; paid: number; pending: number }
  monthly: { months: string[]; values: number[] }
}>()

const search = ref(props.filters.q)
const channel = ref(props.filters.channel)
const status = ref(props.filters.status)
const from = ref(props.filters.from)
const to = ref(props.filters.to)
const pending = ref<string | null>(null)

const COLUMNS = [
  { key: 'donor_name', label: 'Donatur' },
  { key: 'created_at', label: 'Waktu', hideOnMobile: true },
  { key: 'channel', label: 'Kanal', hideOnMobile: true },
  { key: 'amount', label: 'Jumlah', align: 'right' as const },
  { key: 'status', label: 'Status' },
]

/** Satu sumber kebenaran filter: dipakai untuk navigasi sekaligus tautan ekspor. */
const query = computed(() => ({
  q: search.value || undefined,
  channel: channel.value === 'all' ? undefined : channel.value,
  status: status.value === 'all' ? undefined : status.value,
  from: from.value || undefined,
  to: to.value || undefined,
}))

const exportUrl = computed(() => route('admin.donations.export', query.value))

function apply() {
  router.get(route('admin.donations.index'), query.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

watchDebounced(search, apply, { debounce: 350 })
watch([channel, status, from, to], apply)

function changeStatus(row: DonationRow, next: string) {
  if (next === row.status) return

  pending.value = row.order_id

  router.put(
    route('admin.donations.update', row.order_id),
    { status: next },
    { preserveScroll: true, onFinish: () => (pending.value = null) },
  )
}
</script>

<template>
  <AdminLayout title="Donasi" subtitle="Dukungan yang masuk lewat halaman publik">
    <template #actions>
      <a
        :href="exportUrl"
        class="inline-flex items-center gap-2 rounded-xl border border-border-strong bg-surface-raised px-3 py-2 text-sm text-ink"
      >
        <Download class="size-4" />
        Ekspor CSV
      </a>
    </template>

    <div class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatTile
          label="Terkumpul"
          :value="formatRupiah(totals.amount)"
          hint="Tercatat + lunas, sesuai filter"
        />
        <StatTile label="Sudah lunas" :value="formatRupiah(totals.paid)" hint="Sudah dicocokkan superadmin" />
        <StatTile label="Jumlah donasi" :value="formatNumber(totals.count)" hint="Semua status" />
        <StatTile label="Belum dicek" :value="formatNumber(totals.pending)" hint="Belum dicocokkan" />
      </div>

      <MonthlySeriesPanel
        title="Donasi per bulan"
        description="Mengikuti filter di bawah — 12 bulan terakhir"
        :months="monthly.months"
        :values="monthly.values"
        series-name="Donasi"
        :hue="4"
      />

      <Card flush>
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative min-w-48 flex-1">
            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-subtle" />
            <input
              v-model="search"
              type="search"
              placeholder="Cari nama, email, atau kode…"
              class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised pr-3 pl-9 text-sm
                     text-ink placeholder:text-ink-subtle focus:border-brand focus:outline-none"
            />
          </div>

          <select
            v-model="channel"
            class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
            aria-label="Filter kanal"
          >
            <option value="all">Semua kanal</option>
            <option v-for="value in options.channels" :key="value" :value="value">
              {{ channelLabel(value) }}
            </option>
          </select>

          <select
            v-model="status"
            class="h-10 rounded-xl border border-border-strong bg-surface-raised px-3 text-sm text-ink focus:border-brand focus:outline-none"
            aria-label="Filter status"
          >
            <option value="all">Semua status</option>
            <option v-for="value in options.statuses" :key="value" :value="value">
              {{ statusLabel(value) }}
            </option>
          </select>

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
        </div>

        <EmptyState
          v-if="!donations.data.length"
          :icon="Heart"
          title="Belum ada donasi"
          description="Donasi yang masuk lewat halaman /dukung tercatat di sini."
        />

        <DataTable v-else :columns="COLUMNS" :rows="donations.data">
          <template #cell-donor_name="{ row }">
            <p class="font-medium text-ink">
              {{ row.donor_name }}
              <span v-if="row.is_anonymous" class="text-xs font-normal text-ink-subtle">(anonim di publik)</span>
            </p>
            <p class="text-xs text-ink-subtle">{{ row.donor_email ?? row.order_id }}</p>
            <p v-if="row.message" class="mt-1 max-w-md text-xs text-ink-muted italic">"{{ row.message }}"</p>
          </template>
          <template #cell-created_at="{ row }">
            <span class="text-ink-muted">{{ formatIsoDateTime(row.created_at) }}</span>
          </template>
          <template #cell-channel="{ row }">
            <span class="text-ink-muted">{{ channelLabel(row.channel) }}</span>
          </template>
          <template #cell-amount="{ row }">
            <span class="font-medium tabular-nums">{{ formatRupiah(row.amount) }}</span>
          </template>
          <!-- Status diubah langsung dari daftar: donasi manual memang tidak
               diverifikasi otomatis, jadi ini satu-satunya cara menandainya. -->
          <template #cell-status="{ row }">
            <select
              :value="row.status"
              :disabled="pending === row.order_id"
              class="h-9 rounded-xl border border-border-strong bg-surface-raised px-2 text-sm text-ink
                     focus:border-brand focus:outline-none disabled:opacity-50"
              :aria-label="`Status donasi ${row.order_id}`"
              @change="changeStatus(row, ($event.target as HTMLSelectElement).value)"
            >
              <option v-for="value in options.statuses" :key="value" :value="value">
                {{ statusLabel(value) }}
              </option>
            </select>
          </template>
        </DataTable>

        <Pagination :meta="donations" />
      </Card>
    </div>
  </AdminLayout>
</template>
