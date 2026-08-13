<script setup lang="ts">
import { ref } from 'vue'
import { Check, Copy, ExternalLink } from '@lucide/vue'
import Card from '@/Components/ui/Card.vue'
import type { PaymentTargetSettings } from '@/types'

defineProps<{ pay: PaymentTargetSettings }>()

/** Nomor yang barusan disalin — umpan balik sesaat, tanpa toast. */
const copied = ref<string | null>(null)

function copy(value: string) {
  navigator.clipboard?.writeText(value)
  copied.value = value
  window.setTimeout(() => (copied.value = null), 1500)
}
</script>

<template>
  <Card title="Cara berdonasi">
    <div class="space-y-5">
      <div v-if="pay.qris_url" class="flex flex-col items-center gap-2">
        <img
          :src="pay.qris_url"
          alt="Kode QRIS untuk donasi"
          class="w-56 max-w-full rounded-xl border border-border bg-white p-2"
        />
        <p class="text-xs text-ink-subtle">Scan pakai aplikasi apa pun yang mendukung QRIS.</p>
      </div>

      <dl v-if="pay.banks.length" class="space-y-2">
        <div
          v-for="(bank, index) in pay.banks"
          :key="index"
          class="flex items-center justify-between gap-3 rounded-xl border border-border px-3 py-2.5"
        >
          <div class="min-w-0">
            <dt class="text-xs text-ink-muted">{{ bank.bank }}</dt>
            <dd class="truncate font-mono text-sm font-medium text-ink">{{ bank.account_number }}</dd>
            <dd v-if="bank.account_name" class="truncate text-xs text-ink-subtle">
              a.n. {{ bank.account_name }}
            </dd>
          </div>
          <button
            type="button"
            class="shrink-0 rounded-xl border border-border-strong p-2 text-ink-muted transition hover:text-ink"
            :aria-label="`Salin nomor rekening ${bank.bank}`"
            @click="copy(bank.account_number)"
          >
            <Check v-if="copied === bank.account_number" class="size-4 text-success" />
            <Copy v-else class="size-4" />
          </button>
        </div>
      </dl>

      <a
        v-if="pay.saweria_url"
        :href="pay.saweria_url"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center justify-between gap-2 rounded-xl border border-border px-3 py-2.5 text-sm
               text-ink transition hover:bg-surface-sunken"
      >
        Kirim lewat Saweria
        <ExternalLink class="size-4 text-ink-subtle" />
      </a>

      <p v-if="!pay.qris_url && !pay.banks.length && !pay.saweria_url" class="text-sm text-ink-muted">
        Belum ada tujuan pembayaran yang diatur.
      </p>
    </div>
  </Card>
</template>
