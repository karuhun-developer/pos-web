import { formatRupiah } from '@/lib/utils'

/** "2026-08-13" → "13/8". Sumbu tanggal cukup singkat; tahun ada di judul filter. */
export function dayLabel(key: string): string {
  const [, month, day] = key.split('-')

  return `${Number(day)}/${Number(month)}`
}

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

/** "2026-08" → "Agu 26". Dipakai sumbu bulanan di panel superadmin. */
export function monthLabel(key: string): string {
  const [year, month] = key.split('-')

  return `${MONTHS[Number(month) - 1] ?? month} ${year.slice(2)}`
}

/** Sumbu uang: ringkas (12,5 jt) supaya label tidak saling tabrak. */
export function compactRupiah(value: number): string {
  const abs = Math.abs(value)
  const sign = value < 0 ? '-' : ''

  if (abs >= 1_000_000_000) return `${sign}${round(abs / 1_000_000_000)} M`
  if (abs >= 1_000_000) return `${sign}${round(abs / 1_000_000)} jt`
  if (abs >= 1_000) return `${sign}${round(abs / 1_000)} rb`

  return `${sign}${abs}`
}

function round(value: number): string {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value)
}

/**
 * Baris tooltip: kotak warna kecil + nama seri (teks pakai token teks, bukan
 * warna seri) + nilai rata kanan.
 */
export function tooltipRow(color: string, name: string, value: string): string {
  return `<div style="display:flex;align-items:center;gap:8px;margin-top:4px">
    <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:${color}"></span>
    <span style="flex:1">${name}</span>
    <strong style="font-variant-numeric:tabular-nums">${value}</strong>
  </div>`
}

export function tooltipTitle(text: string): string {
  return `<div style="font-weight:600">${text}</div>`
}

export function money(value: number): string {
  return formatRupiah(value)
}
