<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'
import { CameraOff, ScanLine } from '@lucide/vue'
import Modal from '@/Components/ui/Modal.vue'
import { getDetector } from '@/lib/barcode'

const open = defineModel<boolean>('open', { required: true })

/** Format ikut dikirim: dekodernya sudah tahu simbologinya, jadi form tidak perlu menebak. */
const emit = defineEmits<{ detected: [value: string, format: string] }>()

const video = ref<HTMLVideoElement | null>(null)
const error = ref<string | null>(null)
const starting = ref(false)

let stream: MediaStream | null = null
let timer: number | null = null

/*
 * Jeda antar-frame. Dekode tiap frame (60×/detik) cuma memanaskan HP tanpa
 * membaca lebih banyak barcode — mata kamera perlu waktu fokus dulu.
 */
const INTERVAL = 150

async function start() {
  error.value = null
  starting.value = true

  try {
    // facingMode 'environment' = kamera belakang di HP; di laptop diabaikan.
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false,
    })

    if (!open.value) {
      // Izin baru keluar setelah dialog ditutup — jangan tinggalkan kamera menyala.
      stop()

      return
    }

    const element = video.value

    if (element) {
      element.srcObject = stream
      await element.play()
    }

    const detect = await getDetector()

    timer = window.setInterval(async () => {
      const source = video.value

      if (!source || source.readyState < 2) return

      const hit = await detect(source).catch(() => null)

      if (!hit) return

      // Getar pendek sebagai konfirmasi: di HP layarnya sering tidak dilihat
      // saat memindai karena kamera diarahkan ke barang.
      navigator.vibrate?.(40)
      emit('detected', hit.value, hit.format)
      open.value = false
    }, INTERVAL)
  } catch (e) {
    error.value =
      e instanceof DOMException && (e.name === 'NotAllowedError' || e.name === 'SecurityError')
        ? 'Akses kamera ditolak. Izinkan kamera untuk situs ini di pengaturan browser, lalu coba lagi.'
        : 'Kamera tidak bisa dibuka. Pastikan perangkat punya kamera dan tidak sedang dipakai aplikasi lain.'
  } finally {
    starting.value = false
  }
}

function stop() {
  if (timer !== null) {
    window.clearInterval(timer)
    timer = null
  }

  stream?.getTracks().forEach((track) => track.stop())
  stream = null

  if (video.value) video.value.srcObject = null
}

watch(open, (value) => (value ? start() : stop()))

// Pindah halaman saat dialog terbuka tetap harus mematikan lampu kameranya.
onBeforeUnmount(stop)
</script>

<template>
  <Modal v-model:open="open" title="Scan barcode">
    <div v-if="error" class="flex flex-col items-center gap-3 py-6 text-center">
      <CameraOff class="size-8 text-ink-subtle" />
      <p class="text-sm text-ink-muted">{{ error }}</p>
    </div>

    <div v-else class="space-y-3">
      <div class="relative aspect-video w-full overflow-hidden rounded-xl bg-black">
        <video ref="video" class="size-full object-cover" muted playsinline />
        <div class="pointer-events-none absolute inset-x-8 inset-y-10 rounded-xl border-2 border-white/70" />
        <p
          v-if="starting"
          class="absolute inset-0 flex items-center justify-center text-sm text-white/80"
        >
          Menyalakan kamera…
        </p>
      </div>

      <p class="flex items-center justify-center gap-2 text-xs text-ink-muted">
        <ScanLine class="size-4" />
        Arahkan barcode ke dalam kotak. Kodenya terisi sendiri begitu terbaca.
      </p>
    </div>
  </Modal>
</template>
