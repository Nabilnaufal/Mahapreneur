<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Support\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DokuStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mahapreneur.doku.client_id' => 'test-client',
            'mahapreneur.doku.secret_key' => 'test-secret',
            'mahapreneur.doku.base_url' => 'https://api-sandbox.doku.com',
        ]);
    }

    public function test_paid_transaction_on_doku_marks_payment_paid(): void
    {
        [$order, $customer] = $this->orderWithPayment();
        Http::fake(['api-sandbox.doku.com/*' => Http::response([
            'order' => ['invoice_number' => $order->payment->invoice_number, 'amount' => 18000, 'status' => 'ORDER_GENERATED'],
            'transaction' => ['status' => 'SUCCESS'],
            'channel' => ['id' => 'EMONEY_DANA'],
        ])]);

        $this->withToken(AuthToken::issue($customer))
            ->getJson("/api/customer/orders/{$order->id}/payment")
            ->assertOk()
            ->assertJsonPath('data.payment.status', 'PAID');

        $this->assertSame('PAID', $order->payment->fresh()->status);
        $this->assertSame('CONFIRMED', $order->fresh()->status);
    }

    public function test_failed_transaction_on_doku_marks_payment_failed(): void
    {
        [$order, $customer] = $this->orderWithPayment();
        Http::fake(['api-sandbox.doku.com/*' => Http::response([
            'transaction' => ['status' => 'FAILED'],
        ])]);

        $this->withToken(AuthToken::issue($customer))
            ->getJson("/api/customer/orders/{$order->id}/payment")
            ->assertOk()
            ->assertJsonPath('data.payment.status', 'FAILED');

        // Pesanan tetap menunggu pembayaran, customer bisa membuat invoice baru.
        $this->assertSame('WAITING_PAYMENT', $order->fresh()->status);
    }

    public function test_order_list_also_reflects_doku_status(): void
    {
        [$order, $customer] = $this->orderWithPayment();
        Http::fake(['api-sandbox.doku.com/*' => Http::response([
            'transaction' => ['status' => 'SUCCESS'],
            'channel' => ['id' => 'EMONEY_DANA'],
        ])]);

        $this->withToken(AuthToken::issue($customer))
            ->getJson('/api/customer/orders')
            ->assertOk()
            ->assertJsonPath('data.orders.0.paymentStatus', 'PAID');

        $this->assertSame('PAID', $order->payment->fresh()->status);
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
            'order_number' => 'MP-261124-5678',
            'customer_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'WAITING_PAYMENT',
            'payment_status' => 'PENDING',
            'payment_method' => 'DANA',
            'subtotal' => 18000,
            'total' => 18000,
            'recipient_name' => 'Alya',
            'recipient_phone' => '081234567890',
            'address' => 'Kos Melati, Depok',
        ]);
        $order->payment()->create([
            'method' => 'DANA',
            'status' => 'PENDING',
            'amount' => 18000,
            'invoice_number' => 'MP-261124-5678-ABC',
            'doku_channel' => 'EMONEY_DANA',
            'doku_payment_url' => 'https://staging.doku.com/checkout-link-v2/abc',
        ]);
        // DOKU baru memberi status final 60 detik setelah invoice dibuat.
        $order->payment->forceFill(['created_at' => now()->subMinutes(5)])->save();

        return [$order->fresh('payment'), $customer];
    }
}
