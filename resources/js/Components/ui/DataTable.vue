<!--
  Constraint-nya `Record<string, any>`, bukan `Record<string, unknown>`:
  interface biasa (Product, Sale, …) tidak punya index signature implisit, jadi
  versi `unknown` menolak semua tipe baris yang kita punya.
-->
<script setup lang="ts" generic="T extends Record<string, any>">
export interface Column {
  key: string
  label: string
  align?: 'left' | 'right' | 'center'
  /** Sembunyikan di layar sempit — kolom sekunder saja. */
  hideOnMobile?: boolean
}

defineProps<{
  columns: Column[]
  rows: T[]
}>()

const ALIGN = { left: 'text-left', right: 'text-right', center: 'text-center' } as const
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full border-collapse text-sm">
      <thead>
        <tr class="border-b border-border">
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            class="px-5 py-3 text-xs font-medium tracking-wide text-ink-muted uppercase"
            :class="[ALIGN[column.align ?? 'left'], column.hideOnMobile ? 'hidden md:table-cell' : '']"
          >
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, index) in rows"
          :key="(row.id as string) ?? index"
          class="border-b border-border last:border-0 hover:bg-surface-sunken/60"
        >
          <td
            v-for="column in columns"
            :key="column.key"
            class="px-5 py-3 text-ink"
            :class="[ALIGN[column.align ?? 'left'], column.hideOnMobile ? 'hidden md:table-cell' : '']"
          >
            <slot :name="`cell-${column.key}`" :row="row" :index="index">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
