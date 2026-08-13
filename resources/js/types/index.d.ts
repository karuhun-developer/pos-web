declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

/**
 * Nama route Ziggy tersedia global lewat direktif @routes di app.blade.php
 * (datanya) + plugin ZiggyVue di app.ts (fungsinya). Harus dibungkus
 * `declare global`: berkas ini punya `export`, jadi ia modul — deklarasi
 * telanjang di sini tidak akan terlihat sebagai global.
 */
declare global {
  function route(name?: string, params?: any, absolute?: boolean): string
}

/**
 * Template Vue tidak boleh memakai global sembarangan — vue-tsc hanya mengenali
 * daftar global bawaan. Jadi route() didaftarkan juga sebagai properti komponen
 * supaya `route('produk.index')` di dalam <template> ikut ter-type-check.
 */
declare module 'vue' {
  interface ComponentCustomProperties {
    route: typeof route
  }
}

export interface StoreSummary {
  id: number
  name: string
  role: string
}

export interface AuthUser {
  id: number
  name: string
  email: string
  avatar_url: string | null
  current_store_id: number | null
  is_superadmin: boolean
  /** Permission efektif di toko aktif — dipakai untuk menyembunyikan aksi tulis. */
  permissions: string[]
}

/** Bentuk paginator Laravel (paginate()->withQueryString()). */
export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
  links: Array<{ url: string | null; label: string; active: boolean }>
}

/** Kolom sync universal — dimiliki semua entity yang turun ke Android. */
export interface SyncEntity {
  id: string
  created_at: number
  updated_at: number
  deleted_at: number | null
}

export interface Category extends SyncEntity {
  name: string
  color: string | null
  sort_order: number
}

export interface Product extends SyncEntity {
  category_id: string | null
  name: string
  sku: string | null
  barcode: string | null
  barcode_type: string | null
  price: number
  cost: number
  track_stock: number
  stock: number
  image_path: string | null
  /** Diisi controller dari tabel media (bukan kolom tabel produk). */
  image_url?: string | null
  active: number
}

export interface Sale extends SyncEntity {
  session_id: string | null
  number: string
  subtotal: number
  discount: number
  tax: number
  total: number
  paid: number
  change_due: number
  payment_method: string
  status: 'completed' | 'void'
  sold_at: number
}

export interface SaleItem extends SyncEntity {
  sale_id: string
  product_id: string | null
  name_snapshot: string
  price_snapshot: number
  qty: number
  discount: number
  line_total: number
}

export interface CashflowCategory extends SyncEntity {
  name: string
  type: 'income' | 'expense'
  is_system: number
  sort_order: number
}

export interface CashflowEntry extends SyncEntity {
  category_id: string | null
  session_id: string | null
  /** debit = uang masuk, credit = uang keluar. */
  direction: 'debit' | 'credit'
  amount: number
  source: string
  source_ref: string | null
  note: string | null
  occurred_at: number
}

export interface CashierSession extends SyncEntity {
  opened_at: number
  closed_at: number | null
  opening_cash: number
  expected_cash: number
  counted_cash: number | null
  difference: number | null
  status: 'open' | 'closed'
  opened_by: string | null
  note: string | null
  orders_count?: number
  revenue?: number
}

export interface SharedProps {
  auth: {
    user: AuthUser | null
    stores: StoreSummary[]
    current_store: StoreSummary | null
  }
  flash: { success: string | null; error: string | null }
  app: { name: string }
  [key: string]: unknown
}
