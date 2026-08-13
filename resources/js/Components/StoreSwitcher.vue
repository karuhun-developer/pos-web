<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Check, ChevronsUpDown, Store as StoreIcon } from '@lucide/vue'
import type { SharedProps } from '@/types'

const page = usePage<SharedProps>()
const open = ref(false)

const stores = computed(() => page.props.auth.stores ?? [])
const current = computed(() => page.props.auth.current_store)

function pick(id: number) {
  open.value = false
  if (id === current.value?.id) return
  router.post(route('stores.switch', id), {}, { preserveScroll: true })
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="flex w-full items-center gap-2 rounded-xl border border-border bg-surface-raised px-3 py-2
             text-left text-sm transition hover:bg-surface-sunken"
      @click="open = !open"
    >
      <StoreIcon class="size-4 shrink-0 text-ink-muted" />
      <span class="min-w-0 flex-1 truncate font-medium text-ink">
        {{ current?.name ?? 'Pilih toko' }}
      </span>
      <ChevronsUpDown class="size-4 shrink-0 text-ink-subtle" />
    </button>

    <div v-if="open" class="fixed inset-0 z-10" @click="open = false" />

    <ul
      v-if="open"
      class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-border
             bg-surface-raised py-1 shadow-lg"
    >
      <li v-for="store in stores" :key="store.id">
        <button
          type="button"
          class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition hover:bg-surface-sunken"
          @click="pick(store.id)"
        >
          <Check
            class="size-4 shrink-0"
            :class="store.id === current?.id ? 'text-brand' : 'text-transparent'"
          />
          <span class="min-w-0 flex-1 truncate text-ink">{{ store.name }}</span>
          <span class="shrink-0 text-xs text-ink-subtle">{{ store.role }}</span>
        </button>
      </li>
    </ul>
  </div>
</template>
