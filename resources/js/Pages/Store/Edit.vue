<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Crown } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'

interface Member {
  id: number
  name: string
  email: string
  avatar_url: string | null
  role: string
  is_owner: boolean
}

const props = defineProps<{
  store: { id: number; name: string; owner_id: number; created_at: string }
  members: Member[]
  can_update: boolean
}>()

const form = useForm({ name: props.store.name })

const ROLE_LABELS: Record<string, string> = { owner: 'Pemilik', cashier: 'Kasir' }
</script>

<template>
  <AppLayout title="Pengaturan Toko">
    <div class="grid gap-4 lg:grid-cols-2">
      <Card title="Identitas toko" description="Nama ini muncul di struk dan aplikasi kasir.">
        <form class="space-y-4" @submit.prevent="form.put(route('store.update'))">
          <FormField label="Nama toko" required :error="form.errors.name">
            <Input v-model="form.name" :disabled="!can_update" :invalid="!!form.errors.name" />
          </FormField>

          <p v-if="!can_update" class="text-xs text-ink-subtle">
            Hanya pemilik toko yang bisa mengubah pengaturan ini.
          </p>
          <Button v-else type="submit" :disabled="form.processing">Simpan</Button>
        </form>
      </Card>

      <Card title="Anggota" :description="`${members.length} orang punya akses ke toko ini`">
        <ul class="divide-y divide-border">
          <li v-for="member in members" :key="member.id" class="flex items-center gap-3 py-3 first:pt-0">
            <img
              v-if="member.avatar_url"
              :src="member.avatar_url"
              :alt="member.name"
              class="size-9 shrink-0 rounded-full object-cover"
            />
            <div
              v-else
              class="flex size-9 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-xs font-semibold text-ink-muted"
            >
              {{ member.name.charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-ink">{{ member.name }}</p>
              <p class="truncate text-xs text-ink-subtle">{{ member.email }}</p>
            </div>
            <Badge :tone="member.is_owner ? 'brand' : 'neutral'">
              <Crown v-if="member.is_owner" class="size-3" />
              {{ ROLE_LABELS[member.role] ?? member.role }}
            </Badge>
          </li>
        </ul>

        <p class="mt-4 rounded-xl bg-surface-sunken px-3 py-2 text-xs text-ink-muted">
          Undangan anggota baru dilakukan lewat aplikasi kasir. Halaman ini menampilkan daftarnya
          supaya kamu tahu siapa saja yang bisa membaca data toko.
        </p>
      </Card>
    </div>
  </AppLayout>
</template>
