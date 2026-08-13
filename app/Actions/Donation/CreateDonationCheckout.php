<?php

namespace App\Actions\Donation;

use App\Exceptions\PaywuzException;
use App\Models\Donation;
use App\Models\User;
use App\Services\PaywuzClient;

/**
 * Donasi lewat Paywuz: baris donasi dibuat lebih dulu (status `pending`) baru
 * transaksinya. Urutan ini penting — kalau transaksinya dibuat duluan lalu
 * penyimpanan gagal, webhook akan datang membawa order id yang tidak dikenal
 * dan uangnya jadi tidak tercatat.
 */
class CreateDonationCheckout
{
    public function __construct(
        private readonly RecordDonation $record,
        private readonly PaywuzClient $paywuz,
    ) {}

    /**
     * @param  array<string,mixed>  $attributes
     *
     * @throws PaywuzException
     */
    public function handle(array $attributes, ?User $user = null): Donation
    {
        $donation = $this->record->handle($attributes, $user, 'pending');

        try {
            $transaction = $this->paywuz->createTransaction([
                'orderId' => $donation->order_id,
                'amount' => $donation->amount,
                'expiryMinutes' => (int) config('donation.checkout_expiry_minutes'),
                'redirectUrl' => route('donate.thanks', $donation),
                'metadata' => [
                    'donation_id' => $donation->id,
                    'donor_name' => $donation->donor_name,
                ],
            ]);
        } catch (PaywuzException $exception) {
            // Tagihannya tidak pernah jadi, jadi barisnya jangan ditinggal
            // menggantung sebagai "pending" selamanya.
            $donation->update(['status' => 'cancelled']);

            throw $exception;
        }

        $donation->update([
            'reference' => $transaction['id'] ?? null,
            'redirect_url' => $transaction['paymentUrl'] ?? null,
            'raw_response' => $transaction,
        ]);

        if (blank($donation->redirect_url)) {
            $donation->update(['status' => 'cancelled']);

            throw new PaywuzException('Paywuz tidak mengembalikan paymentUrl.');
        }

        return $donation;
    }
}
