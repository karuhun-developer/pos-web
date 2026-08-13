<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { CheckCircle2, TriangleAlert, X } from '@lucide/vue'
import type { SharedProps } from '@/types'

const page = usePage<SharedProps>()
const dismissed = ref(false)

const flash = computed(() => page.props.flash ?? { success: null, error: null })
const message = computed(() => flash.value.error ?? flash.value.success)
const isError = computed(() => Boolean(flash.value.error))

// Flash baru selalu tampil lagi meski yang sebelumnya sudah ditutup.
watch(message, () => {
  dismissed.value = false
})
</script>

<template>
  <Transition name="toast">
    <div
      v-if="message && !dismissed"
      role="status"
      class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-2xl border
             px-4 py-3 shadow-lg sm:left-auto sm:right-6"
      :class="isError
        ? 'border-danger/30 bg-danger-soft text-danger'
        : 'border-success/30 bg-success/10 text-success'"
    >
      <component :is="isError ? TriangleAlert : CheckCircle2" class="mt-0.5 size-4 shrink-0" />
      <p class="flex-1 text-sm">{{ message }}</p>
      <button type="button" aria-label="Tutup" class="shrink-0" @click="dismissed = true">
        <X class="size-4" />
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(0.5rem);
}
</style>
