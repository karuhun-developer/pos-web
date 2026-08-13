<script setup lang="ts">
const model = defineModel<string | number | null>()

withDefaults(
  defineProps<{
    type?: string
    placeholder?: string
    disabled?: boolean
    invalid?: boolean
  }>(),
  { type: 'text', disabled: false, invalid: false },
)
</script>

<template>
  <input
    v-model="model"
    :type="type"
    :placeholder="placeholder"
    :disabled="disabled"
    :aria-invalid="invalid || undefined"
    class="h-10 w-full rounded-xl border bg-surface-raised px-3 text-sm text-ink transition
           placeholder:text-ink-subtle focus:border-brand focus:outline-none
           disabled:opacity-50 aria-[invalid=true]:border-danger"
    :class="invalid ? 'border-danger' : 'border-border-strong'"
  />
</template>
