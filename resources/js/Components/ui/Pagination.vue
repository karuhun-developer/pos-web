<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { Paginated } from '@/types'

defineProps<{ meta: Paginated<unknown> }>()
</script>

<template>
  <nav
    v-if="meta.last_page > 1"
    class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-5 py-3"
    aria-label="Navigasi halaman"
  >
    <p class="text-xs text-ink-muted">
      {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} dari {{ meta.total }}
    </p>
    <div class="flex flex-wrap items-center gap-1">
      <template v-for="(link, index) in meta.links" :key="index">
        <span
          v-if="!link.url"
          class="rounded-lg px-3 py-1.5 text-xs text-ink-subtle"
          v-html="link.label"
        />
        <Link
          v-else
          :href="link.url"
          preserve-scroll
          class="rounded-lg px-3 py-1.5 text-xs transition"
          :class="link.active
            ? 'bg-brand text-brand-ink'
            : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'"
          v-html="link.label"
        />
      </template>
    </div>
  </nav>
</template>
