<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

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
    ) {
        $channel = config('mahapreneur.doku.channels')[$paymentMethod] ?? null;
        if (! $channel) {
            throw new RuntimeException("Metode pembayaran {$paymentMethod} tidak didukung DOKU");
        }

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
                'payment_method_types' => [$channel],
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

    /**
     * Status transaksi menurut DOKU. Jaring pengaman saat notifikasi webhook
     * tidak sampai ke server; status final baru tersedia 60 detik setelah bayar.
     */
    public function transactionStatus(string $invoiceNumber): ?array
    {
        try {
            $response = $this->request('GET', '/orders/v1/status/'.$invoiceNumber);
        } catch (Throwable $error) {
            Log::warning('Gagal cek status DOKU: '.$error->getMessage());

            return null;
        }

        return [
            'status' => strtoupper((string) data_get($response, 'transaction.status', '')),
            'channel' => data_get($response, 'channel.id'),
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
        $lines = [
            'Client-Id:'.config('mahapreneur.doku.client_id'),
            "Request-Id:{$requestId}",
            "Request-Timestamp:{$timestamp}",
            "Request-Target:{$target}",
        ];
        // GET tanpa body tidak boleh menyertakan baris Digest di string to sign.
        if ($digest !== '') {
            $lines[] = "Digest:{$digest}";
        }
        $signature = base64_encode(hash_hmac('sha256', implode("\n", $lines), config('mahapreneur.doku.secret_key'), true));

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
