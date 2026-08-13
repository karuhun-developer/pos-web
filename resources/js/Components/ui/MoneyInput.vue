<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber, parseRupiah } from '@/lib/utils'

/**
 * Input uang bermask ribuan, seperti di POS Kacaw. `<input type="number">`
 * tidak dipakai: ia melarang pemisah ribuan, jadi "150000" harus dihitung
 * nolnya sendiri oleh mata — sumber salah harga yang paling gampang.
 *
 * Nilainya tetap **integer rupiah** — yang bermask cuma tampilannya.
 */
const model = defineModel<number>({ required: true })

withDefaults(
  defineProps<{ placeholder?: string; disabled?: boolean; invalid?: boolean; min?: number }>(),
  { placeholder: '0', disabled: false, invalid: false },
)

// Nol tampil sebagai kotak kosong supaya tidak perlu dihapus dulu sebelum
// mengetik angka pertama.
const display = computed(() => (model.value ? formatNumber(model.value) : ''))

function onInput(event: Event) {
  const target = event.target as HTMLInputElement

  model.value = parseRupiah(target.value)

  // Model bisa saja tidak berubah (mis. mengetik titik), jadi Vue tidak
  // merender ulang — kotaknya disamakan manual supaya karakter yang bukan
  // angka tidak tertinggal di layar.
  target.value = display.value
}
</script>

<template>
  <div class="relative">
    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-subtle">
      Rp
    </span>
    <input
      :value="display"
      type="text"
      inputmode="numeric"
      :placeholder="placeholder"
      :disabled="disabled"
      :aria-invalid="invalid || undefined"
      class="h-10 w-full rounded-xl border bg-surface-raised py-2 pr-3 pl-9 text-sm text-ink transition
             placeholder:text-ink-subtle focus:border-brand focus:outline-none
             disabled:opacity-50 aria-[invalid=true]:border-danger"
      :class="invalid ? 'border-danger' : 'border-border-strong'"
      @input="onInput"
    />
  </div>
</template>
