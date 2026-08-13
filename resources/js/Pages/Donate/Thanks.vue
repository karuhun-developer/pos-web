<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Copy, Heart } from '@lucide/vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import PaymentTargets from '@/Components/donate/PaymentTargets.vue'
import { formatRupiah } from '@/lib/utils'
import type { PaymentTargetSettings } from '@/types'

defineProps<{
  donation: {
    order_id: string
    donor_name: string
    amount: number
    channel: string
    status: string
    message: string | null
  }
  pay: PaymentTargetSettings
}>()

function copy(value: string) {
  navigator.clipboard?.writeText(value)
}
</script>

<template>
  <GuestLayout>
    <div class="mx-auto w-full max-w-lg space-y-6 px-4 py-12">
      <Card>
        <div class="text-center">
          <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand-soft text-brand">
            <Heart class="size-6" />
          </div>
          <h1 class="mt-4 text-lg font-semibold text-ink">Terima kasih!</h1>
          <!-- Yang perlu dibaca di sini: apa yang terjadi setelah ini. Nama &
               pesan ditinjau dulu, jadi jangan sampai orang mengira catatannya
               hilang karena belum muncul di daftar. -->
          <p class="mx-auto mt-1 max-w-sm text-sm text-ink-muted">
            Catatanmu tersimpan. Nama dan pesannya kami tinjau dulu sebelum tampil di halaman
            donasi — biasanya tidak lama.
          </p>
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

        <div class="mt-6 flex justify-center gap-2">
          <Link :href="route('donate.index')">
            <Button variant="outline" size="sm">Halaman donasi</Button>
          </Link>
          <Link :href="route('home')">
            <Button size="sm">Selesai</Button>
          </Link>
        </div>
      </Card>

      <!-- Belum transfer? Tujuannya tetap ada di sini supaya tidak perlu
           kembali ke halaman sebelumnya. -->
      <PaymentTargets :pay="pay" />
    </div>
  </GuestLayout>
</template>
