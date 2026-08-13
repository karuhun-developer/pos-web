<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Controller web memanggil $this->authorize() eksplisit di tiap aksi;
    // kepemilikan data tidak boleh cuma bergantung pada StoreScope.
    use AuthorizesRequests;
}
