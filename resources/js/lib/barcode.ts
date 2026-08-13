/**
 * Pemindai barcode di browser. Dua jalur, dipilih otomatis:
 *
 * 1. `BarcodeDetector` bawaan (Chrome/Edge/Android) — dekodernya milik OS, jadi
 *    paling cepat dan tidak menambah satu byte pun ke bundle.
 * 2. `@zxing/browser` sebagai cadangan (Firefox, Safari desktop). Di-import
 *    **dinamis** supaya ~250 kB dekoder itu hanya diunduh oleh orang yang
 *    benar-benar menekan tombol pindai, bukan oleh setiap pemuatan halaman.
 *
 * Yang dipakai halaman cuma satu fungsi: "ada barcode apa di frame ini?".
 * Kamera, loop, dan UI-nya milik `BarcodeScanner.vue` — dengan begitu kedua
 * jalur di atas berperilaku sama persis dari sisi pemanggil.
 */

/** Format yang dipindai. QR ikut karena banyak barcode toko dicetak sebagai QR. */
const NATIVE_FORMATS = [
  'ean_13',
  'ean_8',
  'upc_a',
  'upc_e',
  'code_128',
  'code_39',
  'itf',
  'codabar',
  'qr_code',
]

export type BarcodeHit = { value: string; format: string }

/** Membaca satu frame; `null` berarti belum ada barcode yang terbaca. */
export type FrameDetector = (video: HTMLVideoElement) => Promise<BarcodeHit | null>

type NativeDetector = {
  detect(source: CanvasImageSource): Promise<Array<{ rawValue: string; format: string }>>
}

type NativeDetectorCtor = {
  new (options?: { formats?: string[] }): NativeDetector
  getSupportedFormats?(): Promise<string[]>
}

function nativeCtor(): NativeDetectorCtor | null {
  return (window as unknown as { BarcodeDetector?: NativeDetectorCtor }).BarcodeDetector ?? null
}

/**
 * Kamera butuh konteks aman: di luar https/localhost `getUserMedia` memang
 * tidak ada, jadi tombol pindai lebih baik tidak ditawarkan sama sekali
 * daripada menjanjikan sesuatu yang pasti gagal.
 */
export function isScanSupported(): boolean {
  return typeof navigator !== 'undefined' && Boolean(navigator.mediaDevices?.getUserMedia)
}

/**
 * Nama format dua jalur itu beda ('ean_13' vs 'EAN_13'), dan keduanya beda lagi
 * dengan simbologi yang disimpan produk. Dipetakan di sini supaya form bisa
 * mengisi simbologi dari hasil pindai — dekodernya sudah tahu, kasir tidak
 * perlu menebaknya lagi.
 */
const SYMBOLOGIES: Record<string, string> = {
  ean_13: 'EAN13',
  ean_8: 'EAN8',
  upc_a: 'UPC',
  upc_e: 'UPC',
  code_128: 'CODE128',
  code_39: 'CODE39',
  itf: 'ITF14',
}

export function toSymbology(format: string): string | null {
  return SYMBOLOGIES[format.toLowerCase()] ?? null
}

let detector: Promise<FrameDetector> | null = null

/**
 * Detektor di-cache: membangun ulang `BrowserMultiFormatReader` tiap kali
 * dialog dibuka berarti mengunduh (dan mem-parse) dekodernya berkali-kali.
 */
export function getDetector(): Promise<FrameDetector> {
  detector ??= build()

  return detector
}

async function build(): Promise<FrameDetector> {
  const Native = nativeCtor()

  if (Native) {
    // Daftar format tiap browser beda; meminta format yang tidak didukung
    // membuat konstruktornya melempar, jadi disaring dulu.
    const supported = (await Native.getSupportedFormats?.()) ?? NATIVE_FORMATS
    const formats = NATIVE_FORMATS.filter((format) => supported.includes(format))

    if (formats.length) {
      const instance = new Native({ formats })

      return async (video) => {
        const [hit] = await instance.detect(video)

        return hit ? { value: hit.rawValue, format: hit.format } : null
      }
    }
  }

  const [{ BrowserMultiFormatReader }, { BarcodeFormat, DecodeHintType }] = await Promise.all([
    import('@zxing/browser'),
    import('@zxing/library'),
  ])

  const hints = new Map([
    [
      DecodeHintType.POSSIBLE_FORMATS,
      [
        BarcodeFormat.EAN_13,
        BarcodeFormat.EAN_8,
        BarcodeFormat.UPC_A,
        BarcodeFormat.UPC_E,
        BarcodeFormat.CODE_128,
        BarcodeFormat.CODE_39,
        BarcodeFormat.ITF,
        BarcodeFormat.CODABAR,
        BarcodeFormat.QR_CODE,
      ],
    ],
  ])

  const reader = new BrowserMultiFormatReader(hints)

  return async (video) => {
    try {
      const result = reader.decode(video)

      return { value: result.getText(), format: BarcodeFormat[result.getBarcodeFormat()] }
    } catch {
      // ZXing melempar NotFoundException untuk frame tanpa barcode — itu
      // kejadian normal puluhan kali per detik, bukan error.
      return null
    }
  }
}
