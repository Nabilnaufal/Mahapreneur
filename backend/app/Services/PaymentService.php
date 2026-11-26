<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Activity;
use App\Support\Reference;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentService
{
    private const SIMULATED_METHODS = ['OVO', 'DANA', 'SHOPEEPAY'];

    public function __construct(private DokuService $doku) {}

    public static function isSimulatedMethod(string $method): bool
    {
        return in_array($method, self::SIMULATED_METHODS, true);
    }

    /**
     * Satu-satunya jalur yang boleh mengubah status pembayaran menjadi PAID.
     * Dipanggil dari webhook DOKU yang sudah tervalidasi, atau simulasi pengembangan.
     */
    public function markPaid(
        string $invoiceNumber,
        ?int $amount = null,
        ?string $channel = null,
        string $source = 'DOKU_WEBHOOK',
        ?array $rawPayload = null,
    ) {
        $payment = Payment::with('order.store')->where('invoice_number', $invoiceNumber)->first();
        if (! $payment) {
            return ['status' => 'NOT_FOUND'];
        }

        if ($amount !== null && $amount !== $payment->amount) {
            Activity::log(
                null,
                'PAYMENT_AMOUNT_MISMATCH',
                'Payment',
                $payment->id,
                "Nominal notifikasi ({$amount}) tidak sama dengan tagihan ({$payment->amount}) untuk {$payment->invoice_number}",
                ['raw' => $rawPayload],
            );

            return ['status' => 'AMOUNT_MISMATCH'];
        }

        if ($payment->status === 'PAID') {
            return ['status' => 'ALREADY_PAID'];
        }

        $order = $payment->order;
        $now = now();

        DB::transaction(function () use ($payment, $order, $channel, $source, $now) {
            $payment->update([
                'status' => 'PAID',
                'paid_at' => $now,
                'doku_channel' => $channel ?? $payment->doku_channel,
            ]);
            $order->update([
                'payment_status' => 'PAID',
                'status' => $order->status === 'WAITING_PAYMENT' ? 'CONFIRMED' : $order->status,
                'confirmed_at' => $order->confirmed_at ?? $now,
            ]);
            $order->histories()->create([
                'status' => 'CONFIRMED',
                'note' => 'Pembayaran diterima'.($source === 'SIMULATION' ? ' (simulasi pengembangan)' : ''),
            ]);
        });

        Activity::notify(
            $order->customer_id,
            'Pembayaran berhasil',
            "Pembayaran pesanan {$order->order_number} sudah kami terima. Pesanan diteruskan ke {$order->store->name}.",
            'success',
            "/customer/orders/{$order->id}",
        );
        Activity::notify(
            $order->store->user_id,
            'Pesanan baru sudah dibayar',
            "Pesanan {$order->order_number} sudah dibayar. Siap diproses.",
            'success',
            "/seller/orders/{$order->id}",
        );
        Activity::log(
            $order->customer_id,
            'PAYMENT_PAID',
            'Payment',
            $payment->id,
            "Pembayaran {$payment->invoice_number} sebesar {$payment->amount} berhasil via {$source}",
        );

        return ['status' => 'PAID', 'orderId' => $order->id, 'orderNumber' => $order->order_number];
    }

    /** Tandai pembayaran gagal/kedaluwarsa tanpa mengubah status pesanan. */
    public function markFailed(string $invoiceNumber, string $status, ?string $note = null): void
    {
        $payment = Payment::with('order')->where('invoice_number', $invoiceNumber)->first();
        if (! $payment || $payment->status === 'PAID') {
            return;
        }

        DB::transaction(function () use ($payment, $status) {
            $payment->update(['status' => $status]);
            $payment->order->update(['payment_status' => $status]);
        });

        Activity::notify(
            $payment->order->customer_id,
            $status === 'EXPIRED' ? 'Pembayaran kedaluwarsa' : 'Pembayaran gagal',
            $note ?? "Silakan lakukan pembayaran ulang untuk pesanan {$payment->order->order_number}",
            'error',
            "/customer/orders/{$payment->order_id}",
        );
    }

    /** Siapkan data pembayaran untuk satu pesanan. */
    public function prepareOnlinePayment(int $orderId): ?array
    {
        $order = Order::with(['payment', 'customer'])->find($orderId);
        if (! $order || $order->payment_method === 'COD') {
            return null;
        }

        $invoice = $order->payment?->invoice_number ?? Reference::invoiceNumber($order->order_number);
        $expiresAt = $order->payment?->expires_at ?? $this->expiry();
        $paymentUrl = $order->payment?->doku_payment_url;
        $tokenId = null;

        if (DokuService::configured() && ! self::isSimulatedMethod($order->payment_method)) {
            try {
                $created = $this->doku->createInvoice(
                    $invoice,
                    $order->total,
                    $order->customer->name,
                    $order->customer->email,
                    "Pesanan {$order->order_number}",
                );
                $paymentUrl = $created['paymentUrl'] ?? $paymentUrl;
                $tokenId = $created['tokenId'];
            } catch (Throwable $error) {
                Log::error('Gagal membuat invoice DOKU: '.$error->getMessage());
            }
        } elseif (! self::isSimulatedMethod($order->payment_method)) {
            $paymentUrl = config('mahapreneur.app_url')."/payment/{$order->order_number}";
        } else {
            // Metode non-DOKU tidak memiliki halaman gateway atau URL pembayaran.
            $paymentUrl = null;
        }

        $payment = Payment::updateOrCreate(['order_id' => $orderId], [
            'method' => $order->payment_method,
            'status' => 'PENDING',
            'amount' => $order->total,
            'invoice_number' => $invoice,
            'doku_payment_url' => $paymentUrl,
            'doku_channel' => self::isSimulatedMethod($order->payment_method)
                ? $order->payment_method
                : match ($order->payment_method) {
                    'QRIS' => config('mahapreneur.doku.qris_channel'),
                    'VIRTUAL_ACCOUNT' => config('mahapreneur.doku.va_channel'),
                    default => config('mahapreneur.doku.ewallet_channel'),
                },
            'expires_at' => $expiresAt,
        ]);

        return [
            'payment' => $payment,
            'tokenId' => $tokenId,
            'simulated' => self::isSimulatedMethod($order->payment_method) || ! DokuService::configured(),
        ];
    }

    /** Daftar channel pembayaran yang tersedia pada metode tertentu. */
    public function availableChannels(string $method)
    {
        if (self::isSimulatedMethod($method)) {
            return [[
                'channelCode' => $method,
                'label' => match ($method) {
                    'OVO' => 'OVO (simulasi)',
                    'DANA' => 'DANA (simulasi)',
                    default => 'ShopeePay (simulasi)',
                },
                'method' => $method,
            ]];
        }

        if (! DokuService::configured()) {
            return array_values(array_filter([
                ['channelCode' => config('mahapreneur.doku.qris_channel'), 'label' => 'QRIS (semua e-wallet & m-banking)', 'method' => 'QRIS'],
                ['channelCode' => config('mahapreneur.doku.va_channel'), 'label' => 'Virtual Account '.config('mahapreneur.doku.va_channel'), 'method' => 'VIRTUAL_ACCOUNT'],
                ['channelCode' => config('mahapreneur.doku.ewallet_channel'), 'label' => 'E-Wallet '.config('mahapreneur.doku.ewallet_channel'), 'method' => 'EWALLET'],
            ], fn ($channel) => $channel['method'] === $method));
        }

        try {
            return array_values(array_filter(
                $this->doku->listChannels(),
                fn ($channel) => $channel['method'] === $method,
            ));
        } catch (Throwable $error) {
            Log::error('Gagal memuat channel DOKU: '.$error->getMessage());

            return [];
        }
    }

    public function expiry(): CarbonInterface
    {
        return now()->addMinutes(config('mahapreneur.doku.payment_ttl_minutes'));
    }
}
