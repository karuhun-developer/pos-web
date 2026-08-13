<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import GithubIcon from '@/Components/GithubIcon.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import FlashToast from '@/Components/FlashToast.vue'

/*
 * Merek yang dipajang ke publik adalah POS Kacaw — aplikasi Androidnya yang
 * dipromosikan. POS Pro cuma nama panel webnya, jadi namanya baru muncul
 * setelah orangnya masuk (lihat AppLayout).
 */
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <Head :title="title">
    <meta v-if="description" head-key="description" name="description" :content="description" />
  </Head>

  <div class="flex min-h-screen flex-col bg-surface">
    <header
      class="sticky top-0 z-30 border-b border-border bg-surface/80 backdrop-blur-md"
    >
      <div class="mx-auto flex h-14 w-full max-w-5xl items-center gap-3 px-4 sm:px-6">
        <Link :href="route('home')" class="flex items-center gap-2">
          <div class="flex size-7 items-center justify-center rounded-lg bg-brand text-brand-ink">
            <Logo class="size-3.5" />
          </div>
          <span class="text-sm font-semibold tracking-tight text-ink">POS Kacaw</span>
        </Link>
        <div class="ml-auto flex items-center gap-1.5">
          <slot name="actions" />
          <ThemeToggle />
        </div>
      </div>
    </header>

    <main class="flex flex-1 flex-col">
      <slot />
    </main>

    <footer class="border-t border-border">
      <div
        class="mx-auto flex w-full max-w-5xl flex-wrap items-center gap-x-5 gap-y-2 px-4 py-6 text-xs
               text-ink-subtle sm:px-6"
      >
        <span class="text-ink-muted">POS Kacaw</span>
        <Link :href="route('donate.index')" class="transition hover:text-ink">Dukung</Link>
        <a
          href="https://github.com/karuhun-developer/pos-android"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-1.5 transition hover:text-ink"
        >
          <GithubIcon class="size-3.5" />
          GitHub
        </a>
        <span class="ml-auto">Gratis 100%</span>
      </div>
    </footer>

    <FlashToast />
  </div>
</template>
