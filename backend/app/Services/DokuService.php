<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/** Klien DOKU SNAP. Secret key tidak pernah dikirim ke frontend. */
class DokuService
{
    public static function configured(): bool
    {
        return filled(config('mahapreneur.doku.client_id')) && filled(config('mahapreneur.doku.secret_key'));
    }

    public function createInvoice(
        string $invoiceNumber,
        int $amount,
        string $customerName,
        string $customerEmail,
        string $description,
        string $paymentMethod,
    )
    {
        $paymentMethodTypes = match ($paymentMethod) {
            'OVO' => ['EMONEY_OVO'],
            'DANA' => ['EMONEY_DANA'],
            'SHOPEEPAY' => ['EMONEY_SHOPEEPAY'],
            'QRIS' => ['QRIS'],
            'VIRTUAL_ACCOUNT' => ['VIRTUAL_ACCOUNT_'.config('mahapreneur.doku.va_channel')],
            'EWALLET' => ['EMONEY_OVO', 'EMONEY_DANA', 'EMONEY_SHOPEEPAY'],
            default => ['QRIS', 'VIRTUAL_ACCOUNT', 'EMONEY_OVO', 'EMONEY_DANA', 'EMONEY_SHOPEEPAY'],
        };

        $response = $this->request('POST', '/checkout/v1/payment', [
            'order' => [
                'amount' => $amount,
                'invoice_number' => $invoiceNumber,
                'currency' => 'IDR',
                'callback_url' => rtrim(config('mahapreneur.app_url'), '/').'/customer/orders',
                'auto_redirect' => true,
                'line_items' => [['name' => $description, 'price' => $amount, 'quantity' => 1]],
            ],
            'payment' => [
                'payment_due_date' => config('mahapreneur.doku.payment_ttl_minutes'),
                'payment_method_types' => $paymentMethodTypes,
            ],
            'customer' => ['name' => $customerName, 'email' => $customerEmail],
        ]);

        $tokenId = data_get($response, 'response.payment.token_id');
        $paymentUrl = data_get($response, 'response.payment.url');
        if (blank($tokenId) || blank($paymentUrl)) {
            throw new RuntimeException('Respons DOKU tidak memiliki token atau URL checkout: '.Str::limit(json_encode($response), 500));
        }

        return [
            'tokenId' => $tokenId,
            'paymentUrl' => $paymentUrl,
            'expiredDate' => data_get($response, 'response.payment.expired_date'),
        ];
    }

    /** Verifikasi signature webhook DOKU, termasuk batas waktu anti-replay. */
    public function verifyNotification(Request $request, string $target): bool
    {
        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $timestamp = $request->header('Request-Timestamp');
        $signature = $request->header('Signature');

        if (! $clientId || ! $requestId || ! $timestamp || ! $signature) {
            return false;
        }

        if (abs(now()->timestamp - strtotime($timestamp)) > 300) {
            return false;
        }

        $rawBody = $request->getContent();
        $digest = $rawBody !== '' ? base64_encode(hash('sha256', $rawBody, true)) : null;
        $lines = [
            "Client-Id:{$clientId}",
            "Request-Id:{$requestId}",
            "Request-Timestamp:{$timestamp}",
            "Request-Target:{$target}",
        ];
        if ($digest) {
            $lines[] = "Digest:{$digest}";
        }

        $expected = base64_encode(hash_hmac('sha256', implode("\n", $lines), config('mahapreneur.doku.secret_key'), true));
        $received = preg_replace('/^HMACSHA256=/i', '', $signature);

        return hash_equals($expected, $received);
    }

    private function request(string $method, string $target, ?array $body = null)
    {
        $requestId = (string) Str::uuid();
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $raw = $body ? json_encode($body) : '';
        $digest = $raw !== '' ? base64_encode(hash('sha256', $raw, true)) : '';
        $stringToSign = implode("\n", [
            'Client-Id:'.config('mahapreneur.doku.client_id'),
            "Request-Id:{$requestId}",
            "Request-Timestamp:{$timestamp}",
            "Request-Target:{$target}",
            "Digest:{$digest}",
        ]);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, config('mahapreneur.doku.secret_key'), true));

        $headers = [
            'Client-Id' => config('mahapreneur.doku.client_id'),
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => "HMACSHA256={$signature}",
            'Content-Type' => 'application/json',
        ];
        if ($digest) {
            $headers['Digest'] = $digest;
        }

        $url = rtrim(config('mahapreneur.doku.base_url'), '/').$target;
        /** @var Response $response */
        $response = Http::withHeaders($headers)->send($method, $url, $raw !== '' ? ['body' => $raw] : []);

        if ($response->failed()) {
            throw new RuntimeException("DOKU {$method} {$target} gagal ({$response->status()}): ".Str::limit($response->body(), 300));
        }

        return $response->json() ?? [];
    }
}
