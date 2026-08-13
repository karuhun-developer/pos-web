/**
 * Registrasi ECharts secara manual (tree-shaken): hanya seri dan komponen yang
 * benar-benar dipakai halaman laporan yang masuk bundle. Menambah bentuk chart
 * baru berarti menambah import-nya di sini — kalau lupa, chart-nya kosong
 * tanpa error yang jelas.
 */
import { use } from 'echarts/core'
import { BarChart, HeatmapChart, LineChart } from 'echarts/charts'
import {
  GridComponent,
  LegendComponent,
  MarkLineComponent,
  TooltipComponent,
  VisualMapComponent,
} from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'

use([
  BarChart,
  LineChart,
  HeatmapChart,
  GridComponent,
  TooltipComponent,
  LegendComponent,
  VisualMapComponent,
  MarkLineComponent,
  CanvasRenderer,
])
