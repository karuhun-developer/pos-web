<script setup lang="ts">
defineProps<{
  label: string
  error?: string | null
  hint?: string | null
  required?: boolean
}>()
</script>

<template>
  <label class="block space-y-1.5">
    <span class="text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </span>
    <slot />
    <span v-if="error" class="block text-xs text-danger">{{ error }}</span>
    <span v-else-if="hint" class="block text-xs text-ink-subtle">{{ hint }}</span>
  </label>
</template>
