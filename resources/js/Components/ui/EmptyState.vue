<script setup lang="ts">
import type { Component } from 'vue'

defineProps<{
  icon?: Component
  title: string
  description?: string
}>()
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
    <component :is="icon" v-if="icon" class="size-10 text-ink-subtle" />
    <div>
      <p class="text-sm font-semibold text-ink">{{ title }}</p>
      <p v-if="description" class="mx-auto mt-1 max-w-sm text-xs text-ink-muted">
        {{ description }}
      </p>
    </div>
    <slot />
  </div>
</template>
