import { ref } from 'vue'

export type Theme = 'light' | 'dark'

const STORAGE_KEY = 'pos-pro-theme'

/** Tema aktif — reaktif supaya chart bisa re-render dengan langkah warna gelapnya. */
export const theme = ref<Theme>('light')

function prefersDark(): boolean {
  return window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false
}

function stored(): Theme | null {
  const value = localStorage.getItem(STORAGE_KEY)

  return value === 'light' || value === 'dark' ? value : null
}

function paint(value: Theme) {
  theme.value = value
  document.documentElement.classList.toggle('dark', value === 'dark')
}

/** Dipanggil sebelum app di-mount: pilihan tersimpan menang atas setelan OS. */
export function applyStoredTheme() {
  paint(stored() ?? (prefersDark() ? 'dark' : 'light'))
}

export function setTheme(value: Theme) {
  localStorage.setItem(STORAGE_KEY, value)
  paint(value)
}

export function toggleTheme() {
  setTheme(theme.value === 'dark' ? 'light' : 'dark')
}
