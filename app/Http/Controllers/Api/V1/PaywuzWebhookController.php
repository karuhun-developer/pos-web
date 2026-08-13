<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Donation\HandlePaywuzWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * Webhook pembayaran donasi. Route-nya dideklarasikan lewat atribut (di luar
 * group `web`) supaya tidak kena CSRF — pengirimnya server Paywuz, bukan
 * browser dengan session.
 */
#[Prefix('api/v1')]
class PaywuzWebhookController extends Controller
{
    #[Post('webhooks/paywuz')]
    public function __invoke(Request $request, HandlePaywuzWebhook $action): JsonResponse
    {
        $signature = (string) $request->header('x-paywuz-signature');
        $secret = (string) config('services.paywuz.webhook_secret');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        // hash_equals: perbandingan waktu-tetap supaya tanda tangan tidak bisa
        // ditebak byte demi byte lewat selisih waktu respons.
        if (blank($signature) || blank($secret) || ! hash_equals($expected, $signature)) {
            Log::warning('tanda tangan webhook paywuz tidak sah', ['received' => $signature]);

            return response()->json(['message' => 'Tanda tangan tidak sah.'], 401);
        }

        $action->handle($request->header('x-paywuz-event'), $request->all());

        // Selalu 200 setelah tanda tangan sah: payload yang tidak dikenal sudah
        // dicatat di log, dan membalas error hanya memicu kiriman ulang.
        return response()->json(['message' => 'ok']);
    }
}
