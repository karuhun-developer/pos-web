<?php

/*
 * Route halaman web (Inertia) dideklarasikan di sini, BUKAN lewat atribut route.
 * Alasannya: paket spatie/laravel-route-attributes memasang route-nya di luar
 * group 'web' (default config-nya cuma SubstituteBindings), jadi controller
 * beratribut tidak dapat session, CSRF, maupun middleware Inertia. Controller
 * API tetap memakai atribut seperti sebelumnya.
 */

require __DIR__.'/web/guest.php';
require __DIR__.'/web/app.php';
require __DIR__.'/web/admin.php';
