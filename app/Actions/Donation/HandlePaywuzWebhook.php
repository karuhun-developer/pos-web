<?php

namespace App\Actions\Donation;

use App\Models\Donation;
use Illuminate\Support\Facades\Log;

/**
 * Memproses webhook Paywuz. IDEMPOTEN: gateway boleh mengirim event yang sama
 * berkali-kali, dan donasi yang sudah `paid` tidak pernah diubah lagi.
 *
 * Payload yang tidak dikenal dicatat lalu diabaikan — membalas error hanya
 * membuat Paywuz mengulang kirim tanpa akhir untuk sesuatu yang memang bukan
 * milik kami.
 */
class HandlePaywuzWebhook
{
    /** @param array<string,mixed> $payload */
    public function handle(?string $event, array $payload): void
    {
        $orderId = $payload['data']['orderId'] ?? $payload['orderId'] ?? null;

        if (blank($orderId)) {
            Log::warning('paywuz webhook tanpa orderId', ['payload' => $payload]);

            return;
        }

        $donation = Donation::where('order_id', $orderId)->first();

        if (! $donation) {
            Log::warning('paywuz webhook order tidak dikenal', ['order_id' => $orderId]);

            return;
        }

        match ($event) {
            'transaction.paid', 'transaction.settlement' => $this->markPaid($donation, $payload),
            'transaction.expired' => $this->markStatus($donation, 'expired', $payload),
            'transaction.cancelled' => $this->markStatus($donation, 'cancelled', $payload),
            default => Log::info('paywuz webhook event diabaikan', ['event' => $event]),
        };
    }

    /** @param array<string,mixed> $payload */
    private function markPaid(Donation $donation, array $payload): void
    {
        if ($donation->status === 'paid') {
            return;
        }

        $donation->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $payload['data']['paymentMethod'] ?? $donation->payment_method,
            'raw_webhook' => $payload,
        ]);
    }

    /** @param array<string,mixed> $payload */
    private function markStatus(Donation $donation, string $status, array $payload): void
    {
        // Uang yang sudah masuk tidak boleh "dibatalkan" oleh event susulan.
        if (in_array($donation->status, ['paid', $status], true)) {
            return;
        }

        $donation->update(['status' => $status, 'raw_webhook' => $payload]);
    }
}
