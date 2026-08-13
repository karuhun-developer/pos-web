<script setup lang="ts">
/*
 * Lambang POS Pro: struk dengan sobekan di bawah. Digambar sebagai satu path
 * `fill-rule="evenodd"` — tiga baris di dalamnya lubang, bukan garis berwarna,
 * jadi lambangnya ikut warna induknya (`currentColor`) dan barisnya otomatis
 * memakai warna latar apa pun yang ada di belakangnya. Satu berkas ini dipakai
 * ulang di `public/favicon.svg` dan ikon Android POS Kacaw.
 */
</script>

<template>
  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path
      fill="currentColor"
      fill-rule="evenodd"
      clip-rule="evenodd"
      d="M6 2.5H18A1.5 1.5 0 0 1 19.5 4V21.5L17.625 19.9L15.75 21.5L13.875 19.9L12 21.5L10.125 19.9L8.25 21.5L6.375 19.9L4.5 21.5V4A1.5 1.5 0 0 1 6 2.5ZM8 6.4H16A0.9 0.9 0 0 1 16 8.2H8A0.9 0.9 0 0 1 8 6.4ZM8 10.2H16A0.9 0.9 0 0 1 16 12H8A0.9 0.9 0 0 1 8 10.2ZM8 14H12A0.9 0.9 0 0 1 12 15.8H8A0.9 0.9 0 0 1 8 14Z"
    />
  </svg>
</template>
