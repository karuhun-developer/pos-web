<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import FormField from '@/Components/ui/FormField.vue'

defineProps<{ googleEnabled: boolean }>()

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' })

function submit() {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout title="Daftar">
    <div class="flex flex-1 items-center justify-center px-4 py-10">
      <div class="w-full max-w-sm">
        <h1 class="text-xl font-semibold text-ink">Buat akun</h1>
        <p class="mt-1 text-sm text-ink-muted">
          Toko pertama dibuatkan otomatis dan kamu jadi pemiliknya.
        </p>

        <a
          v-if="googleEnabled"
          :href="route('google.redirect')"
          class="mt-6 flex h-10 w-full items-center justify-center gap-2 rounded-xl border
                 border-border-strong bg-surface-raised text-sm font-medium text-ink transition
                 hover:bg-surface-sunken"
        >
          Lanjut dengan Google
        </a>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
          <FormField label="Nama" :error="form.errors.name" required>
            <Input v-model="form.name" placeholder="Nama kamu" :invalid="!!form.errors.name" />
          </FormField>

          <FormField label="Email" :error="form.errors.email" required>
            <Input v-model="form.email" type="email" placeholder="kamu@contoh.com" :invalid="!!form.errors.email" />
          </FormField>

          <FormField label="Password" :error="form.errors.password" hint="Minimal 8 karakter." required>
            <Input v-model="form.password" type="password" :invalid="!!form.errors.password" />
          </FormField>

          <FormField label="Ulangi password" required>
            <Input v-model="form.password_confirmation" type="password" />
          </FormField>

          <Button type="submit" :disabled="form.processing" class="w-full">
            {{ form.processing ? 'Memproses…' : 'Daftar' }}
          </Button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
          Sudah punya akun?
          <Link :href="route('login')" class="font-medium text-brand underline">Masuk</Link>
        </p>
      </div>
    </div>
  </GuestLayout>
</template>
