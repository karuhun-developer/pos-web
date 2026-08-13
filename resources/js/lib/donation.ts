/**
 * Label & nada status donasi. Nilai mentahnya (`recorded`, `paywuz`, …) ada di
 * `Donation::STATUSES` / `Donation::CHANNELS`; di layar selalu tampil dalam
 * Bahasa Indonesia dan selalu berupa teks — warna badge cuma penguat.
 */
export type Tone = 'neutral' | 'success' | 'warning' | 'danger' | 'brand'

export const CHANNEL_LABELS: Record<string, string> = {
  manual: 'Transfer manual',
  paywuz: 'Paywuz',
  external: 'Link eksternal',
}

export const STATUS_LABELS: Record<string, string> = {
  recorded: 'Tercatat',
  pending: 'Menunggu',
  paid: 'Lunas',
  expired: 'Kedaluwarsa',
  cancelled: 'Batal',
}

export const STATUS_TONES: Record<string, Tone> = {
  recorded: 'brand',
  pending: 'warning',
  paid: 'success',
  expired: 'neutral',
  cancelled: 'danger',
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
