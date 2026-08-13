<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'outline' | 'ghost' | 'danger' | 'subtle'
    size?: 'sm' | 'md' | 'icon'
    type?: 'button' | 'submit' | 'reset'
    disabled?: boolean
  }>(),
  { variant: 'primary', size: 'md', type: 'button', disabled: false },
)

const VARIANTS = {
  primary: 'bg-brand text-brand-ink hover:opacity-90',
  outline: 'border border-border-strong bg-surface-raised text-ink hover:bg-surface-sunken',
  ghost: 'text-ink-muted hover:bg-surface-sunken hover:text-ink',
  subtle: 'bg-brand-soft text-brand hover:opacity-90',
  danger: 'bg-danger text-white hover:opacity-90',
} as const

const SIZES = {
  sm: 'h-8 gap-1.5 px-3 text-xs',
  md: 'h-10 gap-2 px-4 text-sm',
  icon: 'size-10',
} as const

const classes = computed(() =>
  cn(
    'inline-flex shrink-0 items-center justify-center rounded-xl font-medium transition',
    'disabled:pointer-events-none disabled:opacity-50',
    VARIANTS[props.variant],
    SIZES[props.size],
  ),
)
</script>

<template>
  <button :type="type" :disabled="disabled" :class="classes">
    <slot />
  </button>
</template>
