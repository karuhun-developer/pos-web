<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { Search, ShieldCheck, ShieldOff, Users } from '@lucide/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { formatIsoDate, formatNumber } from '@/lib/utils'
import type { Paginated, SharedProps } from '@/types'

interface UserRow {
  id: number
  name: string
  email: string
  avatar_url: string | null
  stores_count: number
  is_superadmin: boolean
  has_google: boolean
  created_at: string | null
}

const props = defineProps<{
  users: Paginated<UserRow>
  filters: { q: string }
}>()

const page = usePage<SharedProps>()
const currentUserId = computed(() => page.props.auth.user?.id)

const search = ref(props.filters.q)
const pending = ref<number | null>(null)

const COLUMNS = [
  { key: 'name', label: 'Pengguna' },
  { key: 'stores_count', label: 'Toko', align: 'right' as const, hideOnMobile: true },
  { key: 'created_at', label: 'Bergabung', hideOnMobile: true },
  { key: 'access', label: 'Akses' },
  { key: 'actions', label: '', align: 'right' as const },
]

watchDebounced(
  search,
  () => {
    router.get(
      route('admin.users.index'),
      { q: search.value || undefined },
      { preserveState: true, preserveScroll: true, replace: true },
    )
  },
  { debounce: 350 },
)

function toggle(user: UserRow) {
  const message = user.is_superadmin
    ? `Cabut status superadmin dari ${user.name}?`
    : `Jadikan ${user.name} superadmin? Ia akan bisa melihat data semua toko.`

  if (!window.confirm(message)) return

  pending.value = user.id

  router.post(
    route('admin.users.superadmin', user.id),
    {},
    { preserveScroll: true, onFinish: () => (pending.value = null) },
  )
}
</script>

<template>
  <AdminLayout title="Pengguna" :subtitle="`${formatNumber(users.total)} akun terdaftar`">
    <Card flush>
      <div class="border-b border-border p-4">
        <div class="relative max-w-sm">
          <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-subtle" />
          <input
            v-model="search"
            type="search"
            placeholder="Cari nama atau email…"
            class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised pr-3 pl-9 text-sm
                   text-ink placeholder:text-ink-subtle focus:border-brand focus:outline-none"
          />
        </div>
      </div>

      <EmptyState
        v-if="!users.data.length"
        :icon="Users"
        title="Tidak ada pengguna"
        description="Coba kata kunci lain."
      />

      <DataTable v-else :columns="COLUMNS" :rows="users.data">
        <template #cell-name="{ row }">
          <div class="flex items-center gap-3">
            <img
              v-if="row.avatar_url"
              :src="row.avatar_url"
              alt=""
              class="size-8 shrink-0 rounded-full object-cover"
            />
            <span
              v-else
              class="flex size-8 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-xs font-medium text-ink-muted"
              aria-hidden="true"
            >
              {{ row.name.charAt(0).toUpperCase() }}
            </span>
            <div class="min-w-0">
              <p class="truncate font-medium text-ink">{{ row.name }}</p>
              <p class="truncate text-xs text-ink-subtle">{{ row.email }}</p>
            </div>
          </div>
        </template>
        <template #cell-stores_count="{ row }">
          <span class="tabular-nums">{{ formatNumber(row.stores_count) }}</span>
        </template>
        <template #cell-created_at="{ row }">
          <span class="text-ink-muted">{{ formatIsoDate(row.created_at) }}</span>
        </template>
        <template #cell-access="{ row }">
          <div class="flex flex-wrap items-center gap-1.5">
            <Badge v-if="row.is_superadmin" tone="brand">
              <ShieldCheck class="size-3" />
              Superadmin
            </Badge>
            <Badge v-if="row.has_google">Google</Badge>
            <span v-if="!row.is_superadmin && !row.has_google" class="text-xs text-ink-subtle">Pengguna biasa</span>
          </div>
        </template>
        <template #cell-actions="{ row }">
          <!-- Tombolnya dimatikan untuk diri sendiri: server juga menolaknya,
               ini supaya alasannya kelihatan sebelum diklik. -->
          <Button
            variant="outline"
            size="sm"
            :disabled="row.id === currentUserId || pending === row.id"
            :title="row.id === currentUserId ? 'Status superadmin sendiri tidak bisa dicabut dari sini.' : undefined"
            @click="toggle(row)"
          >
            <component :is="row.is_superadmin ? ShieldOff : ShieldCheck" class="size-3.5" />
            {{ row.is_superadmin ? 'Cabut' : 'Jadikan superadmin' }}
          </Button>
        </template>
      </DataTable>

      <Pagination :meta="users" />
    </Card>
  </AdminLayout>
</template>
