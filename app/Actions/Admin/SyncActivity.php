<?php

namespace App\Actions\Admin;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\Models\SyncModel;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Log sync ringkas untuk superadmin.
 *
 * Tidak ada tabel log tersendiri di sini, dan memang tidak perlu: setiap row
 * entity sudah membawa `origin_device` + `updated_at` (epoch ms), jadi "siapa
 * menulis apa, kapan terakhir" bisa dibaca langsung dari data — tanpa tabel
 * audit yang harus ikut dibersihkan dan tanpa risiko log dan data berbeda
 * cerita.
 *
 * Semua query melepas StoreScope eksplisit; alasannya sama seperti
 * PlatformOverview.
 */
class SyncActivity
{
    /** Perangkat yang ditampilkan, diurut dari yang terakhir aktif. */
    private const DEVICES = 30;

    /** @return array<string,mixed> */
    public function handle(): array
    {
        $entities = $this->entities();
        $devices = $this->devices();

        return [
            'entities' => $entities,
            'devices' => $devices,
            'totals' => [
                'devices' => count($devices),
                'web_devices' => count(array_filter($devices, fn (array $row) => $row['is_web'])),
                'rows' => array_sum(array_column($entities, 'rows')),
            ],
        ];
    }

    /**
     * Jumlah row & perubahan terakhir per entity, se-platform.
     *
     * @return list<array<string,mixed>>
     */
    private function entities(): array
    {
        $rows = [];

        foreach ($this->models() as $entity => $model) {
            $query = $model::withoutGlobalScope(StoreScope::class);

            $rows[] = [
                'entity' => $entity,
                'rows' => (clone $query)->whereNull('deleted_at')->count(),
                'deleted' => (clone $query)->whereNotNull('deleted_at')->count(),
                'last_update' => (int) ((clone $query)->max('updated_at') ?? 0),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['last_update'] <=> $a['last_update']);

        return $rows;
    }

    /**
     * Satu baris per (toko, perangkat). Agregasi per tabel dilakukan di SQL
     * (count/max biasa, bukan fungsi khusus driver), penggabungan antar tabel
     * di PHP — jumlah perangkat per toko selalu kecil.
     *
     * @return list<array<string,mixed>>
     */
    private function devices(): array
    {
        $rows = [];

        foreach ($this->models() as $entity => $model) {
            $aggregate = $model::withoutGlobalScope(StoreScope::class)
                ->whereNotNull('origin_device')
                ->groupBy('store_id', 'origin_device')
                ->selectRaw('store_id, origin_device, count(*) as changes, max(updated_at) as last_seen')
                ->get();

            foreach ($aggregate as $row) {
                $key = $row->store_id.'|'.$row->origin_device;
                $seen = (int) $row->last_seen;

                $rows[$key] ??= [
                    'store_id' => (int) $row->store_id,
                    'device' => (string) $row->origin_device,
                    'changes' => 0,
                    'last_seen' => 0,
                    'last_entity' => null,
                ];

                $rows[$key]['changes'] += (int) $row->changes;

                if ($seen > $rows[$key]['last_seen']) {
                    $rows[$key]['last_seen'] = $seen;
                    $rows[$key]['last_entity'] = $entity;
                }
            }
        }

        usort($rows, fn (array $a, array $b) => $b['last_seen'] <=> $a['last_seen']);
        $rows = array_slice($rows, 0, self::DEVICES);

        return $this->label($rows);
    }

    /**
     * Menempelkan nama toko & pemilik perangkat. `origin_device` bernilai
     * "web:{user_id}" untuk tulisan dari web (lihat WriteEntity) — itu
     * diterjemahkan jadi nama orangnya, sisanya id perangkat dari Android yang
     * dipendekkan supaya tabel tetap terbaca.
     *
     * @param  list<array<string,mixed>>  $rows
     * @return list<array<string,mixed>>
     */
    private function label(array $rows): array
    {
        $storeNames = Store::query()
            ->whereIn('id', array_column($rows, 'store_id'))
            ->pluck('name', 'id');

        $userIds = [];
        foreach ($rows as $row) {
            if (Str::startsWith($row['device'], 'web:')) {
                $userIds[] = (int) Str::after($row['device'], 'web:');
            }
        }

        $userNames = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        return array_map(function (array $row) use ($storeNames, $userNames) {
            $isWeb = Str::startsWith($row['device'], 'web:');
            $userId = $isWeb ? (int) Str::after($row['device'], 'web:') : null;

            return [
                ...$row,
                'store' => $storeNames[$row['store_id']] ?? 'Toko terhapus',
                'is_web' => $isWeb,
                'label' => $isWeb
                    ? ($userNames[$userId] ?? 'Pengguna terhapus').' (web)'
                    : Str::limit($row['device'], 20),
            ];
        }, $rows);
    }

    /** @return array<string,class-string<SyncModel>> */
    private function models(): array
    {
        /** @var array<string,class-string<SyncModel>> $entities */
        $entities = config('sync.entities');

        return $entities;
    }
}
