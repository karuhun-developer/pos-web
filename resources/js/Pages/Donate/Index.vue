<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { Heart } from '@lucide/vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'
import PaymentTargets from '@/Components/donate/PaymentTargets.vue'
import { channelLabel } from '@/lib/donation'
import { cn, formatNumber, formatRupiah } from '@/lib/utils'
import type { PaymentTargetSettings, SharedProps } from '@/types'

interface WallEntry {
  name: string
  amount: number
  message: string | null
  at: string | null
}

const props = defineProps<{
  presets: number[]
  limits: { min: number; max: number }
  pay: PaymentTargetSettings
  channels: string[]
  wall: WallEntry[]
  supporters: number
  donor: { name: string; email: string } | null
}>()

const page = usePage<SharedProps>()

const form = useForm({
  donor_name: props.donor?.name ?? '',
  donor_email: props.donor?.email ?? '',
  amount: props.presets[1] ?? props.limits.min,
  message: '',
  channel: props.channels[0] ?? '',
  is_anonymous: false,
})

const custom = ref(false)

function pick(amount: number) {
  custom.value = false
  form.amount = amount
}

function submit() {
  form.post(route('donate.store'))
}

const formatted = computed(() => formatRupiah(form.amount))
</script>

<template>
  <GuestLayout title="Dukung POS Pro">
    <template #actions>
      <Link v-if="page.props.auth.user" :href="route('dashboard')">
        <Button variant="outline" size="sm">Dashboard</Button>
      </Link>
      <Link v-else :href="route('login')">
        <Button variant="outline" size="sm">Masuk</Button>
      </Link>
    </template>

    <!-- Satu kolom sempit: halaman ini cuma perlu menjawab dua hal — ke mana
         uangnya dikirim, dan bagaimana mencatatnya. -->
    <div class="mx-auto w-full max-w-xl space-y-6 px-4 py-10">
      <header>
        <h1 class="text-2xl font-semibold text-ink">Dukung POS Pro</h1>
        <p class="mt-2 text-sm text-ink-muted">
          {{ pay.note ?? 'Aplikasinya gratis dan tetap gratis. Donasi dipakai untuk biaya server.' }}
          <template v-if="supporters">
            {{ formatNumber(supporters) }} orang sudah ikut menopang.
          </template>
        </p>
      </header>

      <PaymentTargets :pay="pay" />

      <Card v-if="channels.length" title="Sudah transfer? Tulis di sini" description="Supaya kami bisa bilang terima kasih.">
        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="preset in presets"
                :key="preset"
                type="button"
                :aria-pressed="!custom && form.amount === preset"
                :class="
                  cn(
                    'h-10 rounded-xl border px-4 text-sm font-medium transition',
                    !custom && form.amount === preset
                      ? 'border-brand bg-brand-soft text-brand'
                      : 'border-border-strong text-ink-muted hover:text-ink',
                  )
                "
                @click="pick(preset)"
              >
                {{ formatRupiah(preset) }}
              </button>
              <button
                type="button"
                :aria-pressed="custom"
                :class="
                  cn(
                    'h-10 rounded-xl border px-4 text-sm font-medium transition',
                    custom
                      ? 'border-brand bg-brand-soft text-brand'
                      : 'border-border-strong text-ink-muted hover:text-ink',
                  )
                "
                @click="custom = true"
              >
                Nominal lain
              </button>
            </div>

            <div v-if="custom" class="mt-3">
              <FormField label="Nominal" required :error="form.errors.amount">
                <Input
                  v-model.number="form.amount"
                  type="number"
                  :invalid="!!form.errors.amount"
                />
              </FormField>
            </div>
            <p v-else-if="form.errors.amount" class="mt-2 text-xs text-danger">
              {{ form.errors.amount }}
            </p>
          </div>

          <!-- Kanal cuma ditanya kalau memang ada pilihan; satu kanal tidak
               perlu dijadikan pertanyaan. -->
          <FormField v-if="channels.length > 1" label="Dikirim lewat" :error="form.errors.channel">
            <select
              v-model="form.channel"
              class="h-10 w-full rounded-xl border border-border-strong bg-surface-raised px-3 text-sm
                     text-ink focus:border-brand focus:outline-none"
            >
              <option v-for="value in channels" :key="value" :value="value">
                {{ channelLabel(value) }}
              </option>
            </select>
          </FormField>

          <div class="grid gap-4 sm:grid-cols-2">
            <FormField label="Nama" required :error="form.errors.donor_name">
              <Input v-model="form.donor_name" :invalid="!!form.errors.donor_name" placeholder="Nama kamu" />
            </FormField>

            <FormField label="Email" :error="form.errors.donor_email" hint="Opsional, tidak ditampilkan.">
              <Input
                v-model="form.donor_email"
                type="email"
                :invalid="!!form.errors.donor_email"
                placeholder="kamu@email.com"
              />
            </FormField>
          </div>

          <FormField
            label="Pesan"
            :error="form.errors.message"
            hint="Tampil di daftar dukungan setelah ditinjau."
          >
            <textarea
              v-model="form.message"
              rows="3"
              maxlength="300"
              class="w-full rounded-xl border border-border-strong bg-surface-raised px-3 py-2 text-sm
                     text-ink focus:border-brand focus:outline-none"
              placeholder="Kepakai banget di warung, makasih!"
            />
          </FormField>

          <label class="flex items-center gap-2 text-sm text-ink-muted">
            <input
              v-model="form.is_anonymous"
              type="checkbox"
              class="size-4 rounded border-border-strong text-brand"
            />
            Tampilkan sebagai anonim
          </label>

          <Button type="submit" :disabled="form.processing">
            <Heart class="size-4" />
            Catat donasi {{ formatted }}
          </Button>
        </form>
      </Card>

      <Card v-else title="Donasi belum dibuka">
        <p class="text-sm text-ink-muted">
          Belum ada cara berdonasi yang aktif. Terima kasih sudah berniat menopang — coba lagi nanti.
        </p>
      </Card>

      <Card v-if="wall.length" title="Dukungan yang masuk" flush>
        <ul class="divide-y divide-border">
          <li v-for="(entry, index) in wall" :key="index" class="px-5 py-3">
            <div class="flex items-baseline justify-between gap-3">
              <p class="truncate text-sm font-medium text-ink">{{ entry.name }}</p>
              <p class="shrink-0 text-xs text-ink-muted">{{ formatRupiah(entry.amount) }}</p>
            </div>
            <p v-if="entry.message" class="mt-1 text-xs text-ink-muted">{{ entry.message }}</p>
          </li>
        </ul>
      </Card>
    </div>
  </GuestLayout>
</template>
