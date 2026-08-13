<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue'
import { theme, toggleTheme } from '@/lib/theme'
</script>

<template>
  <button
    type="button"
    class="inline-flex size-9 items-center justify-center rounded-xl text-ink-muted transition
           hover:bg-surface-sunken hover:text-ink"
    :aria-label="theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
    :title="theme === 'dark' ? 'Mode terang' : 'Mode gelap'"
    @click="toggleTheme"
  >
    <Sun v-if="theme === 'dark'" class="size-4" />
    <Moon v-else class="size-4" />
  </button>
</template>
