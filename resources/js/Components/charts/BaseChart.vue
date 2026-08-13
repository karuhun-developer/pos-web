<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'
import { theme } from '@/lib/theme'
import { chartThemeName, registerChartThemes } from '@/charts/theme'

withDefaults(defineProps<{ option: Record<string, unknown>; height?: number }>(), { height: 260 })

registerChartThemes()

// Tema ikut reaktif: mengganti mode gelap me-render ulang chart dengan langkah
// warna mode itu — bukan membalik warna mode terang.
const name = computed(() => chartThemeName(theme.value))
</script>

<template>
  <VChart
    :key="name"
    :option="option"
    :theme="name"
    :style="{ height: `${height}px`, width: '100%' }"
    autoresize
  />
</template>
