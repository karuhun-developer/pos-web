/**
 * Label & nada status donasi. Nilai mentahnya (`pending`, `qris`, …) ada di
 * `Donation::STATUSES` / `Donation::CHANNELS`; di layar selalu tampil dalam
 * Bahasa Indonesia dan selalu berupa teks — warna badge cuma penguat.
 */
export type Tone = 'neutral' | 'success' | 'warning' | 'danger' | 'brand'

export const CHANNEL_LABELS: Record<string, string> = {
  qris: 'QRIS',
  transfer: 'Transfer bank',
  saweria: 'Saweria',
}

export const STATUS_LABELS: Record<string, string> = {
  pending: 'Menunggu ditinjau',
  approved: 'Diterima',
  rejected: 'Ditolak',
}

export const STATUS_TONES: Record<string, Tone> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
}

export function channelLabel(value: string): string {
  return CHANNEL_LABELS[value] ?? value
}

export function statusLabel(value: string): string {
  return STATUS_LABELS[value] ?? value
}

export function statusTone(value: string): Tone {
  return STATUS_TONES[value] ?? 'neutral'
}
