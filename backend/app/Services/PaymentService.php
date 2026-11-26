<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Activity;
use App\Support\Reference;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentService
{
    public function __construct(private DokuService $doku) {}

    /**
     * Satu-satunya jalur yang boleh mengubah status pembayaran menjadi PAID.
     * Dipanggil dari webhook DOKU yang sudah tervalidasi.
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

        DB::transaction(function () use ($payment, $order, $channel, $now) {
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
                'note' => 'Pembayaran diterima',
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

    /**
     * Selaraskan status dengan DOKU Check Status API. Dipakai saat notifikasi
     * webhook tidak sampai, supaya status pesanan tetap mengikuti keadaan asli.
     */
    public function syncFromDoku(Payment $payment): void
    {
        if ($payment->status !== 'PENDING' || blank($payment->invoice_number)) {
            return;
        }
        // Status final baru tersedia 60 detik setelah pembayaran dibuat.
        if (! $payment->created_at || $payment->created_at->gt(now()->subMinute())) {
            return;
        }
        // Batasi satu panggilan per pembayaran agar halaman tidak membanjiri DOKU.
        if (! Cache::add('doku-sync-'.$payment->id, 1, 20)) {
            return;
        }

        $state = $this->doku->transactionStatus($payment->invoice_number);
        if (! $state) {
            return;
        }

        if (in_array($state['status'], ['SUCCESS', 'SUCCESSFUL', 'PAID', 'SETTLED'], true)) {
            $this->markPaid($payment->invoice_number, null, $state['channel'], 'DOKU_STATUS_SYNC');
        } elseif ($state['status'] === 'EXPIRED') {
            $this->markFailed($payment->invoice_number, 'EXPIRED');
        } elseif ($state['status'] === 'FAILED') {
            $this->markFailed($payment->invoice_number, 'FAILED');
        }
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
        if (! DokuService::configured()) {
            throw ApiException::badRequest('Pembayaran online DOKU belum tersedia. Silakan pilih COD atau hubungi admin.');
        }

        $hasUsableCheckout = filled($order->payment?->doku_token_id) && filled($order->payment?->doku_payment_url);
        $invoice = $hasUsableCheckout
            ? $order->payment->invoice_number
            : Reference::invoiceNumber($order->order_number);
        $expiresAt = $hasUsableCheckout && $order->payment->expires_at?->isFuture()
            ? $order->payment->expires_at
            : $this->expiry();
        $paymentUrl = $order->payment?->doku_payment_url;
        $tokenId = null;

        try {
            $created = $this->doku->createInvoice(
                $invoice,
                $order->total,
                $order->customer->name,
                $order->customer->email,
                "Pesanan {$order->order_number}",
                $order->payment_method,
            );
            $paymentUrl = $created['paymentUrl'] ?? $paymentUrl;
            $tokenId = $created['tokenId'];
        } catch (Throwable $error) {
            Log::error('Gagal membuat invoice DOKU: '.$error->getMessage());
            // Alasan asli DOKU ikut ditampilkan supaya channel/kredensial yang salah cepat ketahuan.
            throw ApiException::badRequest('DOKU gagal membuat pembayaran: '.$error->getMessage());
        }

        $payment = Payment::updateOrCreate(['order_id' => $orderId], [
            'method' => $order->payment_method,
            'status' => 'PENDING',
            'amount' => $order->total,
            'invoice_number' => $invoice,
            'doku_payment_url' => $paymentUrl,
            'doku_token_id' => $tokenId ?? $order->payment?->doku_token_id,
            'doku_channel' => config('mahapreneur.doku.channels')[$order->payment_method] ?? $order->payment_method,
            'expires_at' => $expiresAt,
        ]);

        return [
            'payment' => $payment,
            'tokenId' => $tokenId,
        ];
    }

    public function expiry(): CarbonInterface
    {
        return now()->addMinutes(config('mahapreneur.doku.payment_ttl_minutes'));
    }
}
