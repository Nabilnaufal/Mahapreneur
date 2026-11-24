<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentNotification;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DokuNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'test-client';

    private const SECRET = 'test-secret';

    private const TARGET = '/api/payments/doku/notification';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mahapreneur.doku.client_id' => self::CLIENT_ID,
            'mahapreneur.doku.secret_key' => self::SECRET,
        ]);
    }

    public function test_valid_signature_marks_payment_paid_once(): void
    {
        [$order, $payment] = $this->orderWithPayment();
        $body = json_encode([
            'order' => ['invoice_number' => $payment->invoice_number, 'amount' => $payment->amount],
            'transaction' => ['status' => 'SUCCESS', 'date' => '2026-11-24T05:00:00Z'],
        ], JSON_UNESCAPED_SLASHES);

        $this->postSigned($body)->assertOk()->assertJsonPath('data.status', 'PAID');

        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('CONFIRMED', $order->fresh()->status);
        $this->assertDatabaseHas('payment_notifications', ['signature_valid' => true, 'processed' => true]);

        // Notifikasi yang sama tidak boleh memproses pembayaran dua kali.
        $this->postSigned($body)->assertOk();
        $this->assertSame(1, PaymentNotification::count());
    }

    public function test_invalid_signature_is_rejected_without_writing(): void
    {
        [$order, $payment] = $this->orderWithPayment();
        $body = json_encode([
            'order' => ['invoice_number' => $payment->invoice_number, 'amount' => $payment->amount],
            'transaction' => ['status' => 'SUCCESS', 'date' => '2026-11-24T05:00:00Z'],
        ], JSON_UNESCAPED_SLASHES);

        $this->postSigned($body, 'HMACSHA256=palsu')->assertStatus(401);

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame(0, PaymentNotification::count());
    }

    private function orderWithPayment(): array
    {
        $seller = User::create([
            'name' => 'Rani',
            'email' => 'rani@example.test',
            'password' => 'Rahasia123',
            'role' => 'SELLER',
            'email_verified_at' => now(),
        ]);
        $store = Store::create([
            'user_id' => $seller->id,
            'name' => 'Dapur Nusantara',
            'slug' => 'dapur-nusantara',
            'status' => 'ACTIVE',
        ]);
        $customer = User::create([
            'name' => 'Alya',
            'email' => 'alya@example.test',
            'password' => 'Rahasia123',
            'role' => 'CUSTOMER',
            'email_verified_at' => now(),
        ]);
        $order = Order::create([
            'order_number' => 'MP-261124-1234',
            'customer_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'WAITING_PAYMENT',
            'payment_status' => 'PENDING',
            'payment_method' => 'QRIS',
            'subtotal' => 18000,
            'total' => 18000,
            'recipient_name' => 'Alya',
            'recipient_phone' => '081234567890',
            'address' => 'Kos Melati, Depok',
        ]);
        $payment = $order->payment()->create([
            'method' => 'QRIS',
            'status' => 'PENDING',
            'amount' => 18000,
            'invoice_number' => 'MP-261124-1234-ABC',
            'doku_channel' => 'QRIS',
        ]);

        return [$order, $payment];
    }

    private function postSigned(string $body, ?string $signature = null)
    {
        $requestId = (string) Str::uuid();
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $digest = base64_encode(hash('sha256', $body, true));

        if ($signature === null) {
            $stringToSign = implode("\n", [
                'Client-Id:'.self::CLIENT_ID,
                "Request-Id:{$requestId}",
                "Request-Timestamp:{$timestamp}",
                'Request-Target:'.self::TARGET,
                "Digest:{$digest}",
            ]);
            $signature = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $stringToSign, self::SECRET, true));
        }

        return $this->call(
            'POST',
            self::TARGET,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_CLIENT_ID' => self::CLIENT_ID,
                'HTTP_REQUEST_ID' => $requestId,
                'HTTP_REQUEST_TIMESTAMP' => $timestamp,
                'HTTP_SIGNATURE' => $signature,
                'HTTP_DIGEST' => $digest,
            ],
            $body,
        );
    }
}
