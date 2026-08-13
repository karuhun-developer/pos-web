<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { Building2, ExternalLink, Heart, QrCode, Sparkles } from '@lucide/vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import FormField from '@/Components/ui/FormField.vue'
import Input from '@/Components/ui/Input.vue'
import { cn, formatNumber, formatRupiah } from '@/lib/utils'
import type { SharedProps } from '@/types'

interface ManualChannel {
  bank: string | null
  account_number: string | null
  account_name: string | null
  qris_url: string | null
}

interface WallEntry {
  name: string
  amount: number
  message: string | null
  at: string | null
}

const props = defineProps<{
  presets: number[]
  limits: { min: number; max: number }
  manual: ManualChannel | null
  external: Array<{ label: string; url: string }>
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
  channel: 'manual',
  is_anonymous: false,
})

const custom = ref(false)

/** Kanal yang benar-benar bisa dipakai — kanal yang belum dikonfigurasi tidak ditawarkan. */
const channels = computed(() =>
  props.manual
    ? [
        {
          value: 'manual',
          label: 'Transfer manual',
          hint: 'Transfer sendiri lalu catat di sini. Tidak ada verifikasi.',
          icon: Building2,
        },
      ]
    : [],
)

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

    <div class="mx-auto w-full max-w-5xl px-4 py-10 lg:px-8">
      <header class="max-w-2xl">
        <span
          class="inline-flex items-center gap-1.5 rounded-full bg-brand-soft px-3 py-1 text-xs font-medium text-brand"
        >
          <Heart class="size-3.5" />
          Donasi sukarela
        </span>
        <h1 class="mt-4 text-2xl font-semibold text-ink sm:text-3xl">
          Bantu POS Pro tetap gratis
        </h1>
        <p class="mt-2 text-sm text-ink-muted">
          Aplikasinya bebas dipakai selamanya — donasi tidak membuka fitur apa pun. Yang masuk
          dipakai untuk biaya server dan perawatan.
          <template v-if="supporters">
            Sejauh ini {{ formatNumber(supporters) }} orang sudah ikut menopang.
          </template>
        </p>
      </header>

      <div class="mt-8 grid gap-6 lg:grid-cols-5">
        <!-- Formulir -->
        <div class="lg:col-span-3">
          <Card v-if="channels.length" title="Nominal donasi">
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
                      :min="limits.min"
                      :max="limits.max"
                      :invalid="!!form.errors.amount"
                    />
                  </FormField>
                </div>
                <p v-else-if="form.errors.amount" class="mt-2 text-xs text-danger">
                  {{ form.errors.amount }}
                </p>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Nama" required :error="form.errors.donor_name">
                  <Input
                    v-model="form.donor_name"
                    :invalid="!!form.errors.donor_name"
                    placeholder="Nama kamu"
                  />
                </FormField>

                <FormField
                  label="Email"
                  :error="form.errors.donor_email"
                  hint="Opsional — hanya untuk konfirmasi."
                >
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
                hint="Boleh dikosongkan. Pesan yang tidak anonim tampil di dinding donatur."
              >
                <textarea
                  v-model="form.message"
                  rows="3"
                  maxlength="300"
                  class="w-full rounded-xl border border-border-strong bg-surface-raised px-3 py-2 text-sm text-ink focus:border-brand focus:outline-none"
                  placeholder="Terima kasih, aplikasinya kepakai banget di warung."
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

              <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-ink">Metode</legend>
                <label
                  v-for="channel in channels"
                  :key="channel.value"
                  :class="
                    cn(
                      'flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition',
                      form.channel === channel.value
                        ? 'border-brand bg-brand-soft/40'
                        : 'border-border hover:bg-surface-sunken',
                    )
                  "
                >
                  <input
                    v-model="form.channel"
                    type="radio"
                    :value="channel.value"
                    class="mt-1 size-4 border-border-strong text-brand"
                  />
                  <component :is="channel.icon" class="mt-0.5 size-4 shrink-0 text-ink-subtle" />
                  <span class="min-w-0">
                    <span class="block text-sm font-medium text-ink">{{ channel.label }}</span>
                    <span class="block text-xs text-ink-muted">{{ channel.hint }}</span>
                  </span>
                </label>
              </fieldset>

              <div class="flex flex-wrap items-center gap-3 border-t border-border pt-4">
                <Button type="submit" :disabled="form.processing">
                  <Heart class="size-4" />
                  Donasi {{ formatted }}
                </Button>
                <p class="text-xs text-ink-subtle">Nomor rekening ditampilkan di langkah berikutnya.</p>
              </div>
            </form>
          </Card>

          <!-- Tanpa kanal apa pun, "lewat tautan di samping" menunjuk ke kolom
               yang juga kosong. Jadi teksnya ikut kondisi sebenarnya. -->
          <Card v-else :title="external.length ? 'Donasi lewat tautan' : 'Donasi belum dibuka'">
            <p class="text-sm text-ink-muted">
              {{
                external.length
                  ? 'Kanal pembayaran langsung belum aktif. Dukungan bisa dikirim lewat tautan di samping.'
                  : 'Belum ada kanal donasi yang aktif. Terima kasih sudah berniat menopang — coba lagi nanti.'
              }}
            </p>
          </Card>
        </div>

        <!-- Kanal lain & dinding donatur -->
        <div class="space-y-6 lg:col-span-2">
          <Card v-if="external.length" title="Platform lain">
            <ul class="space-y-2">
              <li v-for="link in external" :key="link.url">
                <a
                  :href="link.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="flex items-center justify-between gap-2 rounded-xl border border-border px-3 py-2.5 text-sm text-ink transition hover:bg-surface-sunken"
                >
                  {{ link.label }}
                  <ExternalLink class="size-3.5 text-ink-subtle" />
                </a>
              </li>
            </ul>
          </Card>

          <Card v-if="manual" title="Transfer manual">
            <dl class="space-y-2 text-sm">
              <div v-if="manual.bank" class="flex justify-between gap-3">
                <dt class="text-ink-muted">Bank</dt>
                <dd class="font-medium text-ink">{{ manual.bank }}</dd>
              </div>
              <div v-if="manual.account_number" class="flex justify-between gap-3">
                <dt class="text-ink-muted">Nomor</dt>
                <dd class="font-mono font-medium text-ink">{{ manual.account_number }}</dd>
              </div>
              <div v-if="manual.account_name" class="flex justify-between gap-3">
                <dt class="text-ink-muted">Atas nama</dt>
                <dd class="font-medium text-ink">{{ manual.account_name }}</dd>
              </div>
            </dl>
            <a
              v-if="manual.qris_url"
              :href="manual.qris_url"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-brand"
            >
              <QrCode class="size-3.5" />
              Lihat QRIS
            </a>
          </Card>

          <Card v-if="wall.length" title="Dinding donatur" flush>
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

          <p class="flex items-start gap-2 text-xs text-ink-subtle">
            <Sparkles class="mt-px size-3.5 shrink-0" aria-hidden="true" />
            Donasi tidak menambah fitur, tidak ada langganan, dan tidak ada iklan.
          </p>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>
