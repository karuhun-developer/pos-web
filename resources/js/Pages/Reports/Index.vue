<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Download, Printer } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatTile from '@/Components/StatTile.vue'
import PeriodFilter from '@/Components/reports/PeriodFilter.vue'
import SalesTrendPanel from '@/Components/reports/SalesTrendPanel.vue'
import HourlyHeatmapPanel from '@/Components/reports/HourlyHeatmapPanel.vue'
import TopProductsPanel from '@/Components/reports/TopProductsPanel.vue'
import PaymentMixPanel from '@/Components/reports/PaymentMixPanel.vue'
import CategoryMarginPanel from '@/Components/reports/CategoryMarginPanel.vue'
import CashflowPanel from '@/Components/reports/CashflowPanel.vue'
import SessionVariancePanel from '@/Components/reports/SessionVariancePanel.vue'
import InventoryPanel from '@/Components/reports/InventoryPanel.vue'
import { formatNumber, formatRupiah } from '@/lib/utils'
import type {
  CashflowDaily,
  CategoryMargin,
  HourlyHeatmap,
  InventorySnapshot,
  PaymentMix,
  ReportPeriod,
  SalesSummary,
  SessionVariance,
  TopProducts,
} from '@/types/reports'

const props = defineProps<{
  period: ReportPeriod
  summary: SalesSummary
  heatmap: HourlyHeatmap
  top_products: TopProducts
  payment_mix: PaymentMix
  category_margin: CategoryMargin
  cashflow: CashflowDaily
  sessions: SessionVariance
  inventory: InventorySnapshot
}>()

// Rentang aktif ikut ke halaman cetak — kertasnya harus memuat angka yang
// sama persis dengan yang sedang dilihat di layar.
const printUrl = computed(() =>
  route('reports.print', {
    preset: props.period.preset,
    from: props.period.preset === 'custom' ? props.period.from : undefined,
    to: props.period.preset === 'custom' ? props.period.to : undefined,
  }),
)

/*
 * Saat rentang diganti, panel lama ditahan dengan opacity turun alih-alih
 * diganti skeleton: tinggi halaman tidak berubah, jadi tidak ada lompatan
 * layout dan mata tetap di tempat yang sama.
 */
const loading = ref(false)
const stop = [
  router.on('start', () => (loading.value = true)),
  router.on('finish', () => (loading.value = false)),
]

onUnmounted(() => stop.forEach((off) => off()))
</script>

<template>
  <AppLayout title="Laporan" subtitle="Semua panel mengikuti satu rentang waktu di atas">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <PeriodFilter :period="props.period" />

      <div class="flex items-center gap-2">
        <!-- Halaman cetak bukan Inertia (HTML polos untuk kertas), jadi <a>
             biasa ke tab baru — bukan <Link>, yang akan mencoba mem-boot Vue. -->
        <a
          :href="printUrl"
          target="_blank"
          rel="noopener"
          class="inline-flex h-9 items-center gap-2 rounded-xl border border-border-strong bg-surface-raised px-3 text-xs font-medium text-ink hover:bg-surface-sunken"
        >
          <Printer class="size-4" />
          Cetak / PDF
        </a>

        <Link
          :href="route('io.index')"
          class="inline-flex h-9 items-center gap-2 rounded-xl border border-border-strong bg-surface-raised px-3 text-xs font-medium text-ink hover:bg-surface-sunken"
        >
          <Download class="size-4" />
          Ekspor data
        </Link>
      </div>
    </div>

    <div class="mt-4 space-y-4 transition-opacity duration-150" :class="loading ? 'opacity-60' : 'opacity-100'">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatTile
          label="Omzet"
          :value="formatRupiah(summary.kpi.revenue)"
          :delta="summary.kpi.delta.revenue"
          :series="summary.trend.current"
        />
        <StatTile
          label="Transaksi"
          :value="formatNumber(summary.kpi.orders)"
          :delta="summary.kpi.delta.orders"
        />
        <StatTile
          label="Rata-rata keranjang"
          :value="formatRupiah(summary.kpi.basket)"
          :delta="summary.kpi.delta.basket"
        />
        <StatTile
          label="Laba kotor (estimasi)"
          :value="formatRupiah(summary.kpi.profit)"
          :delta="summary.kpi.delta.profit"
          hint="Memakai harga modal produk saat ini"
        />
      </div>

      <SalesTrendPanel :trend="summary.trend" />

      <HourlyHeatmapPanel :heatmap="heatmap" />

      <!-- items-start: tanpa ini kartu yang pendek diregangkan setinggi
           tetangganya dan menyisakan kotak kosong yang besar di bawah chart. -->
      <div class="grid items-start gap-4 xl:grid-cols-2">
        <TopProductsPanel :top="top_products" />
        <CategoryMarginPanel :margin="category_margin" />
      </div>

      <!-- items-start: tanpa ini kartu yang pendek diregangkan setinggi
           tetangganya dan menyisakan kotak kosong yang besar di bawah chart. -->
      <div class="grid items-start gap-4 xl:grid-cols-2">
        <PaymentMixPanel :mix="payment_mix" />
        <SessionVariancePanel :sessions="sessions" />
      </div>

      <CashflowPanel :cashflow="cashflow" />

      <InventoryPanel :inventory="inventory" />
    </div>
  </AppLayout>
</template>
