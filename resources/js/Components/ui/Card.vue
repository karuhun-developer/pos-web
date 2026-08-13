<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
  /** Buang padding badan kartu — dipakai kartu yang isinya tabel penuh. */
  flush?: boolean
}>()
</script>

<template>
  <section class="rounded-2xl border border-border bg-surface-raised shadow-sm">
    <header
      v-if="title || $slots.actions"
      class="flex items-start justify-between gap-4 border-b border-border px-5 py-4"
    >
      <div class="min-w-0">
        <h2 v-if="title" class="truncate text-sm font-semibold text-ink">{{ title }}</h2>
        <p v-if="description" class="mt-0.5 text-xs text-ink-muted">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
        <slot name="actions" />
      </div>
    </header>
    <div :class="flush ? '' : 'p-5'">
      <slot />
    </div>
  </section>
</template>
