<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{ tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'brand' }>(),
  { tone: 'neutral' },
)

// Warna status selalu ditemani teks — tidak pernah warna saja (aksesibilitas).
const TONES = {
  neutral: 'bg-surface-sunken text-ink-muted',
  brand: 'bg-brand-soft text-brand',
  success: 'bg-success/12 text-success',
  warning: 'bg-warning/15 text-warning',
  danger: 'bg-danger-soft text-danger',
} as const

const classes = computed(() => TONES[props.tone])
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
    :class="classes"
  >
    <slot />
  </span>
</template>
