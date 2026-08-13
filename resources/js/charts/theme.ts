/**
 * Tema ECharts untuk halaman laporan.
 *
 * Dua aturan yang menentukan isi berkas ini:
 *
 * 1. **Warna dihitung, bukan dikira-kira.** Kedua palet kategorikal di bawah
 *    lolos `scripts/validate_palette.js` (skill dataviz) terhadap surface
 *    plot-nya masing-masing — terang `#fcfcfb`, gelap `#1a1a19`. Mode gelap
 *    punya langkah warnanya sendiri, bukan hasil membalik mode terang.
 *    Mengubah satu hex pun berarti menjalankan ulang validatornya.
 * 2. **Urutan hue kategorikal tetap dan tidak pernah didaur ulang.** Seri
 *    kesembilan bukan warna baru — ia dilebur jadi "Lainnya" (lihat
 *    `TopProducts` yang sudah memotong di 10 + "Lainnya").
 *
 * Catatan mode terang: `#1baf7a`, `#eda100`, dan `#e87ba4` kontrasnya di bawah
 * 3:1 terhadap surface terang. Validator menandainya WARN, dan itu WAJIB
 * ditebus — setiap chart di halaman laporan punya label langsung dan tampilan
 * tabel, jadi identitas seri tidak pernah bergantung pada warna saja.
 */
import { registerTheme } from 'echarts/core'
import './echarts'
import type { Theme } from '@/lib/theme'

export type Mode = Theme

/** Palet kategorikal tervalidasi — urutan slot ini tidak boleh diacak. */
export const CATEGORICAL: Record<Mode, string[]> = {
  light: ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'],
  dark: ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'],
}

/** Magnitudo = satu hue, terang→gelap (mode gelap: redup→terang). */
export const SEQUENTIAL: Record<Mode, string[]> = {
  light: ['#eaf2fd', '#c3dbf7', '#93bcef', '#5f9ae4', '#2a78d6', '#1b559e'],
  dark: ['#15263c', '#1b3c66', '#245a95', '#2f74c4', '#3987e5', '#7db4f0'],
}

/** Polaritas = dua hue + abu netral di titik nol. Bukan hue di tengahnya. */
export const DIVERGING: Record<Mode, { positive: string; negative: string; neutral: string }> = {
  light: { positive: '#2a78d6', negative: '#e34948', neutral: '#cbc9c2' },
  dark: { positive: '#3987e5', negative: '#e66767', neutral: '#4a4a43' },
}

/**
 * Token teks & garis, menyalin nilai dari `resources/css/app.css`. Canvas tidak
 * bisa membaca `var(--color-ink)`, jadi nilainya dituliskan ulang di sini —
 * kalau token di CSS berubah, berkas ini ikut diubah.
 *
 * `surface` adalah warna area plot (ChartCard memberi area plot `bg-surface`),
 * yaitu surface yang dipakai saat memvalidasi palet — sekaligus warna sela 2px
 * antar segmen batang.
 */
export const TOKENS: Record<Mode, Record<'ink' | 'inkMuted' | 'inkSubtle' | 'border' | 'surface' | 'surfaceRaised', string>> = {
  light: {
    ink: '#0b0b0b',
    inkMuted: '#52514e',
    inkSubtle: '#77756f',
    border: '#e3e1dc',
    surface: '#fcfcfb',
    surfaceRaised: '#ffffff',
  },
  dark: {
    ink: '#ffffff',
    inkMuted: '#c3c2b7',
    inkSubtle: '#92918a',
    border: '#34342f',
    surface: '#1a1a19',
    surfaceRaised: '#232322',
  },
}

/** Ujung data batang dibulatkan 4px; sisi yang menempel baseline tetap siku. */
export const BAR_RADIUS = 4

/**
 * Warna teks yang ditulis DI ATAS sebuah mark. Putih di atas `#eda100` praktis
 * tidak terbaca, jadi tintanya dipilih dari luminansi isian — bukan dipukul
 * rata satu warna untuk semua slot palet.
 */
export function inkOn(fill: string): string {
  const hex = fill.replace('#', '')
  const channel = (offset: number) => {
    const value = parseInt(hex.slice(offset, offset + 2), 16) / 255

    return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
  }
  const luminance = 0.2126 * channel(0) + 0.7152 * channel(2) + 0.0722 * channel(4)

  // Tidak bergantung mode: yang menentukan cuma seberapa terang isiannya.
  return luminance > 0.4 ? TOKENS.light.ink : TOKENS.dark.ink
}

const FONT = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"

function build(mode: Mode) {
  const token = TOKENS[mode]

  const axisText = { color: token.inkSubtle, fontSize: 11, fontFamily: FONT }
  // Grid & sumbu sengaja recessive: yang harus terbaca duluan adalah marknya.
  const axisCommon = {
    axisLine: { show: false },
    axisTick: { show: false },
    axisLabel: axisText,
    splitLine: { lineStyle: { color: token.border, type: 'dashed' as const } },
  }

  return {
    color: CATEGORICAL[mode],
    backgroundColor: 'transparent',
    textStyle: { fontFamily: FONT, color: token.inkMuted },
    animationDuration: 240,
    grid: { left: 8, right: 12, top: 16, bottom: 8, containLabel: true },
    categoryAxis: { ...axisCommon, splitLine: { show: false } },
    valueAxis: axisCommon,
    logAxis: axisCommon,
    timeAxis: axisCommon,
    // Sela 2px antar isian: tiap batang membawa 1px border sewarna surface,
    // jadi dua segmen yang bersebelahan terpisah 2px.
    bar: { itemStyle: { borderColor: token.surface, borderWidth: 1 } },
    line: {
      lineStyle: { width: 2 },
      symbolSize: 8,
      symbol: 'circle',
      // Cincin surface 2px supaya marker yang bertumpuk tetap terpisah.
      itemStyle: { borderColor: token.surface, borderWidth: 2 },
    },
    legend: {
      icon: 'roundRect',
      itemWidth: 10,
      itemHeight: 10,
      itemGap: 16,
      // Teks legend memakai token teks, bukan warna serinya — warna dibawa
      // oleh kotak kecil di sebelahnya.
      textStyle: { color: token.inkMuted, fontSize: 12, fontFamily: FONT },
    },
    tooltip: {
      backgroundColor: token.surfaceRaised,
      borderColor: token.border,
      borderWidth: 1,
      padding: [8, 12],
      textStyle: { color: token.ink, fontSize: 12, fontFamily: FONT },
      axisPointer: {
        type: 'line',
        lineStyle: { color: token.border, width: 1 },
        crossStyle: { color: token.border, width: 1 },
        label: { backgroundColor: token.inkSubtle, fontFamily: FONT },
      },
    },
    visualMap: {
      textStyle: { color: token.inkSubtle, fontSize: 11, fontFamily: FONT },
    },
  }
}

const NAMES: Record<Mode, string> = { light: 'pos-light', dark: 'pos-dark' }

let registered = false

/** Dipanggil BaseChart; idempoten supaya tiap chart boleh memanggilnya. */
export function registerChartThemes() {
  if (registered) return
  registered = true

  registerTheme(NAMES.light, build('light'))
  registerTheme(NAMES.dark, build('dark'))
}

export function chartThemeName(mode: Mode): string {
  return NAMES[mode]
}
