<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Package } from '@lucide/vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import FlashToast from '@/Components/FlashToast.vue'

defineProps<{ title: string }>()
</script>

<template>
  <Head :title="title" />

  <div class="flex min-h-screen flex-col bg-surface">
    <header class="flex h-16 shrink-0 items-center gap-3 px-4 lg:px-8">
      <Link :href="route('home')" class="flex items-center gap-2">
        <div class="flex size-8 items-center justify-center rounded-lg bg-brand text-brand-ink">
          <Package class="size-4" />
        </div>
        <span class="text-sm font-semibold text-ink">POS Pro</span>
      </Link>
      <div class="ml-auto flex items-center gap-2">
        <slot name="actions" />
        <ThemeToggle />
      </div>
    </header>

    <main class="flex flex-1 flex-col">
      <slot />
    </main>

    <FlashToast />
  </div>
</template>
