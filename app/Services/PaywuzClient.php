<?php

namespace App\Services;

use App\Exceptions\PaywuzException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Pembungkus tipis REST API Paywuz (gateway pembayaran). Memakai Http client
 * Laravel — bukan curl mentah — supaya bisa di-fake di test.
 */
class PaywuzClient
{
    public static function configured(): bool
    {
        return filled(config('services.paywuz.key'));
    }

    /**
     * Buat transaksi hosted-checkout; yang dipakai pemanggil adalah
     * `paymentUrl` (tempat donatur diarahkan) dan `id` (referensi kami).
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     *
     * @throws PaywuzException
     */
    public function createTransaction(array $payload): array
    {
        $response = $this->http()->post('transactions', $payload);

        if ($response->failed()) {
            throw new PaywuzException('Paywuz createTransaction gagal: '.$response->body());
        }

        return $response->json('data', []);
    }

    /**
     * Ambil transaksi berdasarkan order id kami — dipakai untuk merekonsiliasi
     * webhook yang tidak sampai.
     *
     * @return array<string,mixed>
     *
     * @throws PaywuzException
     */
    public function getTransaction(string $orderId): array
    {
        $response = $this->http()->get('transactions/'.$orderId);

        if ($response->failed()) {
            throw new PaywuzException('Paywuz getTransaction gagal: '.$response->body());
        }

        return $response->json('data', []);
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('services.paywuz.base_url'))
            ->withToken((string) config('services.paywuz.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }
}
