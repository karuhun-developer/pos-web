<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { CircleCheck, Clock, Copy, Heart } from '@lucide/vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import { formatRupiah } from '@/lib/utils'

const props = defineProps<{
  donation: {
    order_id: string
    donor_name: string
    amount: number
    channel: string
    status: string
    message: string | null
  }
  manual: {
    bank: string | null
    account_number: string | null
    account_name: string | null
    qris_url: string | null
  } | null
}>()

// Status donasi diterjemahkan ke kalimat, bukan cuma badge berwarna: yang perlu
// dibaca orang di sini adalah "apa yang harus saya lakukan sekarang".
const state = computed(() => {
  if (props.donation.status === 'paid') {
    return {
      icon: CircleCheck,
      title: 'Pembayaran diterima',
      body: 'Terima kasih banyak — dukunganmu sudah masuk.',
    }
  }

  if (props.donation.channel === 'manual') {
    return {
      icon: Heart,
      title: 'Catatan donasi tersimpan',
      body: 'Silakan transfer ke rekening di bawah. Kami mencatat tanpa verifikasi, jadi tidak perlu kirim bukti.',
    }
  }

  return {
    icon: Clock,
    title: 'Menunggu pembayaran',
    body: 'Selesaikan pembayaran di halaman gateway. Status di sini ikut berubah setelah pembayaran masuk.',
  }
})

function copy(value: string) {
  navigator.clipboard?.writeText(value)
}
</script>

<template>
  <GuestLayout title="Terima kasih">
    <div class="mx-auto w-full max-w-lg px-4 py-12">
      <Card>
        <div class="text-center">
          <div
            class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand-soft text-brand"
          >
            <component :is="state.icon" class="size-6" />
          </div>
          <h1 class="mt-4 text-lg font-semibold text-ink">{{ state.title }}</h1>
          <p class="mx-auto mt-1 max-w-sm text-sm text-ink-muted">{{ state.body }}</p>
        </div>

        <dl class="mt-6 space-y-2 rounded-xl border border-border bg-surface p-4 text-sm">
          <div class="flex justify-between gap-3">
            <dt class="text-ink-muted">Nominal</dt>
            <dd class="font-semibold text-ink">{{ formatRupiah(donation.amount) }}</dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-ink-muted">Atas nama</dt>
            <dd class="text-ink">{{ donation.donor_name }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3">
            <dt class="text-ink-muted">Kode</dt>
            <dd class="flex items-center gap-1.5">
              <span class="font-mono text-xs text-ink">{{ donation.order_id }}</span>
              <button
                type="button"
                class="text-ink-subtle transition hover:text-ink"
                aria-label="Salin kode donasi"
                @click="copy(donation.order_id)"
              >
                <Copy class="size-3.5" />
              </button>
            </dd>
          </div>
        </dl>

        <div v-if="manual" class="mt-4 rounded-xl border border-border p-4">
          <p class="text-xs font-medium text-ink">Tujuan transfer</p>
          <dl class="mt-2 space-y-1.5 text-sm">
            <div v-if="manual.bank" class="flex justify-between gap-3">
              <dt class="text-ink-muted">Bank</dt>
              <dd class="font-medium text-ink">{{ manual.bank }}</dd>
            </div>
            <div v-if="manual.account_number" class="flex items-center justify-between gap-3">
              <dt class="text-ink-muted">Nomor</dt>
              <dd class="flex items-center gap-1.5">
                <span class="font-mono font-medium text-ink">{{ manual.account_number }}</span>
                <button
                  type="button"
                  class="text-ink-subtle transition hover:text-ink"
                  aria-label="Salin nomor rekening"
                  @click="copy(manual.account_number!)"
                >
                  <Copy class="size-3.5" />
                </button>
              </dd>
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
            class="mt-3 inline-block text-xs font-medium text-brand"
          >
            Bayar pakai QRIS
          </a>
        </div>

        <div class="mt-6 flex justify-center gap-2">
          <Link :href="route('donate.index')">
            <Button variant="outline" size="sm">Kembali ke halaman donasi</Button>
          </Link>
          <Link :href="route('home')">
            <Button size="sm">Selesai</Button>
          </Link>
        </div>
      </Card>
    </div>
  </GuestLayout>
</template>
