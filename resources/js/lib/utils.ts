/** Gabung class Tailwind seadanya — cukup untuk kebutuhan di sini (tanpa merge konflik). */
export function cn(...classes: Array<string | false | null | undefined>): string {
  return classes.filter(Boolean).join(' ')
}

/** Uang disimpan sebagai bilangan bulat rupiah (minor unit = rupiah, bukan sen). */
export function formatRupiah(value: number | null | undefined): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value ?? 0)
}

export function formatNumber(value: number | null | undefined): string {
  return new Intl.NumberFormat('id-ID').format(value ?? 0)
}

/** Persen ringkas untuk delta KPI (mis. +12,4%). */
export function formatDelta(value: number | null | undefined): string {
  if (value === null || value === undefined || !Number.isFinite(value)) return '—'
  const sign = value > 0 ? '+' : ''

  return `${sign}${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value)}%`
}

/** Timestamp entity sync = epoch milidetik. */
export function formatDateTime(epochMs: number | null | undefined): string {
  if (!epochMs) return '—'

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Asia/Jakarta',
  }).format(new Date(epochMs))
}

/**
 * Epoch ms → "YYYY-MM-DD" untuk <input type="date">, menurut zona toko.
 * toISOString() tidak dipakai karena hasilnya UTC — tanggal bisa mundur sehari
 * untuk catatan yang dibuat sebelum pukul 07.00 WIB.
 */
export function toDateInput(epochMs: number | null | undefined): string {
  if (!epochMs) return ''

  const parts = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    timeZone: 'Asia/Jakarta',
  }).format(new Date(epochMs))

  return parts
}

/**
 * Tabel platform (toko, pengguna, donasi) bukan entity sync — timestamp-nya
 * string ISO-8601 dari Eloquent, bukan epoch ms.
 */
export function formatIsoDate(value: string | null | undefined): string {
  if (!value) return '—'

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeZone: 'Asia/Jakarta',
  }).format(new Date(value))
}

export function formatIsoDateTime(value: string | null | undefined): string {
  if (!value) return '—'

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Asia/Jakarta',
  }).format(new Date(value))
}

export function formatDate(epochMs: number | null | undefined): string {
  if (!epochMs) return '—'

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeZone: 'Asia/Jakarta',
  }).format(new Date(epochMs))
}
