<script setup lang="ts">
import { watch } from 'vue'
import { X } from '@lucide/vue'

const open = defineModel<boolean>('open', { required: true })

withDefaults(defineProps<{ title?: string; size?: 'md' | 'lg' | 'xl' }>(), { size: 'md' })

// Kunci scroll body selama dialog terbuka supaya latar tidak ikut bergeser.
watch(open, (value) => {
  document.body.style.overflow = value ? 'hidden' : ''
})

function close() {
  open.value = false
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
        <div class="absolute inset-0 bg-black/40" @click="close" />
        <div
          role="dialog"
          aria-modal="true"
          class="relative z-10 max-h-[90vh] w-full overflow-y-auto rounded-t-2xl border border-border
                 bg-surface-raised shadow-xl sm:rounded-2xl"
          :class="{ 'sm:max-w-lg': size === 'md', 'sm:max-w-2xl': size === 'lg', 'sm:max-w-4xl': size === 'xl' }"
        >
          <header class="flex items-center justify-between gap-4 border-b border-border px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">{{ title }}</h2>
            <button
              type="button"
              class="rounded-lg p-1 text-ink-muted transition hover:bg-surface-sunken hover:text-ink"
              aria-label="Tutup"
              @click="close"
            >
              <X class="size-4" />
            </button>
          </header>
          <div class="p-5">
            <slot />
          </div>
          <footer
            v-if="$slots.footer"
            class="flex items-center justify-end gap-2 border-t border-border px-5 py-4"
          >
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
