<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import FormField from '@/Components/ui/FormField.vue'

defineProps<{ googleEnabled: boolean }>()

const form = useForm({ email: '', password: '', remember: false })

function submit() {
  form.post(route('login'), { onFinish: () => form.reset('password') })
}
</script>

<template>
  <GuestLayout title="Masuk">
    <div class="flex flex-1 items-center justify-center px-4 py-10">
      <div class="w-full max-w-sm">
        <h1 class="text-xl font-semibold text-ink">Masuk ke panel web</h1>
        <p class="mt-1 text-sm text-ink-muted">Kelola produk, transaksi, dan laporan tokomu.</p>

        <a
          v-if="googleEnabled"
          :href="route('google.redirect')"
          class="mt-6 flex h-10 w-full items-center justify-center gap-2 rounded-xl border
                 border-border-strong bg-surface-raised text-sm font-medium text-ink transition
                 hover:bg-surface-sunken"
        >
          <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.76h3.57c2.08-1.92 3.27-4.74 3.27-8.09Z" />
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.76c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z" />
            <path fill="#FBBC05" d="M5.84 14.11a6.6 6.6 0 0 1 0-4.22V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.84Z" />
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.05l3.66 2.84C6.71 7.3 9.14 5.38 12 5.38Z" />
          </svg>
          Masuk dengan Google
        </a>

        <div v-if="googleEnabled" class="my-6 flex items-center gap-3">
          <span class="h-px flex-1 bg-border" />
          <span class="text-xs text-ink-subtle">atau pakai email</span>
          <span class="h-px flex-1 bg-border" />
        </div>

        <form class="space-y-4" :class="googleEnabled ? '' : 'mt-6'" @submit.prevent="submit">
          <FormField label="Email" :error="form.errors.email" required>
            <Input v-model="form.email" type="email" placeholder="kamu@contoh.com" :invalid="!!form.errors.email" />
          </FormField>

          <FormField label="Password" :error="form.errors.password" required>
            <Input v-model="form.password" type="password" placeholder="••••••••" :invalid="!!form.errors.password" />
          </FormField>

          <label class="flex items-center gap-2 text-sm text-ink-muted">
            <input v-model="form.remember" type="checkbox" class="size-4 rounded border-border-strong accent-brand" />
            Ingat saya
          </label>

          <Button type="submit" :disabled="form.processing" class="w-full">
            {{ form.processing ? 'Memproses…' : 'Masuk' }}
          </Button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
          Belum punya akun?
          <Link :href="route('register')" class="font-medium text-brand underline">Daftar</Link>
        </p>
      </div>
    </div>
  </GuestLayout>
</template>
