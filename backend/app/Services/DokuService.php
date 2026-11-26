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

    public function createInvoice(string $invoiceNumber, int $amount, string $customerName, string $customerEmail, string $description)
    {
        $response = $this->request('POST', '/checkout/v1/payment', [
            'order' => [
                'amount' => $amount,
                'invoice_number' => $invoiceNumber,
                'currency' => 'IDR',
                'callback_url' => rtrim(config('mahapreneur.app_url'), '/').'/customer/orders',
                'line_items' => [['name' => $description, 'price' => $amount, 'quantity' => 1]],
            ],
            'payment' => [
                'payment_due_date' => config('mahapreneur.doku.payment_ttl_minutes'),
                'payment_method_types' => ['QRIS', 'VIRTUAL_ACCOUNT', 'EMONEY'],
            ],
            'customer' => ['name' => $customerName, 'email' => $customerEmail],
        ]);

        return [
            'tokenId' => data_get($response, 'response.payment.token_id'),
            'paymentUrl' => data_get($response, 'response.payment.url'),
            'expiredDate' => data_get($response, 'response.payment.expired_date'),
        ];
    }

    public function listChannels()
    {
        $response = $this->request('GET', '/checkout/v1/payment/methods');
        $methods = data_get($response, 'response.payment_methods', []);

        return array_map(fn ($method) => [
            'channelCode' => $method['channel_code'] ?? $method['payment_method_type'] ?? '',
            'label' => $method['name'] ?? $method['channel_code'] ?? '',
            'method' => match ($method['payment_method_type'] ?? '') {
                'VIRTUAL_ACCOUNT' => 'VIRTUAL_ACCOUNT',
                'EMONEY' => 'EWALLET',
                default => 'QRIS',
            },
        ], $methods);
    }

    /** Aktifkan satu channel pada invoice, lalu ambil data bayar (QR / nomor VA). */
    public function requestChannel(string $tokenId, string $channelCode)
    {
        $response = $this->request('POST', "/checkout/v1/payment/{$tokenId}/channels", [
            'channel_code' => $channelCode,
        ]);
        $channel = data_get($response, 'response.channel', []);

        return [
            'qrString' => $channel['qr_code'] ?? null,
            'vaNumber' => $channel['virtual_account_number'] ?? $channel['pay_code'] ?? null,
            'paymentUrl' => $channel['url'] ?? data_get($response, 'response.payment.url'),
            'channelCode' => $channelCode,
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
