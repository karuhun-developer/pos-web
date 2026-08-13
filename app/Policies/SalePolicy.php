<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreOwnership;

class SalePolicy
{
    use ChecksStoreOwnership;

    public function viewAny(User $user): bool
    {
        return $this->memberOfCurrentStore($user);
    }

    public function view(User $user, Sale $sale): bool
    {
        return $this->ownsRow($user, $sale);
    }

    /**
     * Melihat daftar transaksi (pekerjaan kasir sehari-hari) berbeda dengan
     * membaca laporan omzet, margin, dan selisih laci seluruh toko — yang
     * terakhir butuh `reports.view`, izin yang tidak dipunyai role cashier.
     */
    public function viewReports(User $user): bool
    {
        return $this->memberOfCurrentStore($user) && $user->can('reports.view');
    }

    /**
     * Transaksi dibuat di kasir (Android), bukan di web. Web hanya membaca dan
     * membatalkan.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Membatalkan transaksi = koreksi pembukuan, bukan pekerjaan kasir harian.
     */
    public function void(User $user, Sale $sale): bool
    {
        return $this->ownsRow($user, $sale) && $user->can('sale.void');
    }
}
