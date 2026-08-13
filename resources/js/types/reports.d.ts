/**
 * Bentuk payload halaman laporan — cerminan langsung dari `app/Actions/Report/*`.
 * Semua uang bilangan bulat rupiah, semua kunci hari "YYYY-MM-DD" (zona toko).
 */

export interface ReportPeriod {
  preset: 'today' | '7d' | '30d' | '90d' | 'custom'
  from: string
  to: string
  days: number
}

export interface SalesSummary {
  kpi: {
    revenue: number
    orders: number
    basket: number
    profit: number
    /** null = tidak ada pembanding (periode sebelumnya nol), bukan 0%. */
    delta: { revenue: number | null; orders: number | null; basket: number | null; profit: number | null }
  }
  trend: {
    days: string[]
    current: number[]
    previous: number[]
    previous_days: string[]
  }
}

export interface HourlyHeatmap {
  days: string[]
  hours: string[]
  /** [jam, hari, omzet] — sel kosong tetap dikirim sebagai 0. */
  cells: Array<[number, number, number]>
  max: number
}

export interface TopProductRow {
  name: string
  qty: number
  revenue: number
}

export interface TopProducts {
  rows: TopProductRow[]
  other: TopProductRow | null
}

export interface PaymentMixRow {
  method: string
  label: string
  orders: number
  revenue: number
  share: number
}

export interface PaymentMix {
  total: number
  rows: PaymentMixRow[]
}

export interface CategoryMarginRow {
  name: string
  revenue: number
  cost: number
  margin: number
  margin_pct: number
}

export interface CategoryMargin {
  rows: CategoryMarginRow[]
}

export interface CashflowDaily {
  days: string[]
  income: number[]
  /** Sudah bertanda negatif dari server — jangan dibalik lagi di sini. */
  expense: number[]
  net: number[]
}

export interface SessionVarianceRow {
  id: string
  label: string
  cashier: string | null
  expected: number
  counted: number
  difference: number
}

export interface SessionVariance {
  rows: SessionVarianceRow[]
  balanced: number
  short: number
  over: number
  worst: number
}

export interface InventoryRow {
  id: string
  name: string
  sku: string | null
  stock: number
  value: number
  active: number
}

export interface InventorySnapshot {
  tracked: number
  value: number
  threshold: number
  out_of_stock: number
  low: InventoryRow[]
  low_total: number
}
