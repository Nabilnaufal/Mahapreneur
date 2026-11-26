<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\PaymentNotification;
use App\Services\DokuService;
use App\Services\PaymentService;
use App\Support\Activity;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private const SUCCESS_STATES = ['SUCCESS', 'SUCCESSFUL', 'PAID', 'SETTLED', 'CAPTURE'];

    private const FAILED_STATES = ['FAILED', 'FAILURE', 'DENIED', 'CANCELLED'];

    private const EXPIRED_STATES = ['EXPIRED', 'TIMEOUT'];

    public function __construct(
        private DokuService $doku,
        private PaymentService $payments,
    ) {}

    /** Metode pembayaran yang disiapkan platform (dipakai halaman checkout). */
    public function methods()
    {
        $methods = [
            ['code' => 'OVO', 'label' => 'OVO', 'description' => 'Pembayaran OVO (simulasi sementara)', 'type' => 'online'],
            ['code' => 'DANA', 'label' => 'DANA', 'description' => 'Pembayaran DANA (simulasi sementara)', 'type' => 'online'],
            ['code' => 'SHOPEEPAY', 'label' => 'ShopeePay', 'description' => 'Pembayaran ShopeePay (simulasi sementara)', 'type' => 'online'],
            ['code' => 'COD', 'label' => 'Bayar di Tempat (COD)', 'description' => 'Bayar langsung ke seller saat pesanan diterima', 'type' => 'cod'],
        ];

        if (DokuService::configured()) {
            $methods = [
                ...$methods,
                ['code' => 'QRIS', 'label' => 'QRIS DOKU', 'description' => 'Bayar dengan QRIS melalui halaman pembayaran DOKU', 'type' => 'online'],
                ['code' => 'VIRTUAL_ACCOUNT', 'label' => 'Virtual Account DOKU', 'description' => 'Transfer ke Virtual Account bank yang tersedia di DOKU', 'type' => 'online'],
                ['code' => 'EWALLET', 'label' => 'E-Wallet DOKU', 'description' => 'Pilih e-wallet yang aktif di halaman pembayaran DOKU', 'type' => 'online'],
            ];
        }

        return $this->ok([
            'simulated' => true,
            'dokuConfigured' => DokuService::configured(),
            'methods' => $methods,
        ]);
    }

    /**
     * Webhook notifikasi pembayaran DOKU.
     * Status pembayaran hanya berubah setelah signature dan nominal diverifikasi.
     */
    public function dokuNotification(Request $request)
    {
        if (! DokuService::configured()) {
            throw ApiException::unauthorized('DOKU belum dikonfigurasi pada server ini');
        }

        $target = '/api/payments/doku/notification';
        $signatureValid = $this->doku->verifyNotification($request, $target);
        $body = $request->json()->all();
        $order = $body['order'] ?? [];
        $transaction = $body['transaction'] ?? [];

        $invoice = (string) ($order['invoice_number'] ?? $body['invoice_number'] ?? '');
        $orderNumber = (string) ($body['order_number'] ?? implode('-', array_slice(explode('-', $invoice), 0, 3)));
        $status = strtoupper((string) ($transaction['status'] ?? $body['transaction_status'] ?? ''));
        $amount = (int) ($order['amount'] ?? $body['amount'] ?? 0) ?: null;

        // Signature diperiksa lebih dulu: notifikasi palsu tidak boleh menulis apa pun,
        // agar tidak bisa memblokir notifikasi asli lewat kunci idempotency.
        if (! $signatureValid) {
            Activity::log(
                null,
                'DOKU_SIGNATURE_INVALID',
                'Payment',
                $invoice ?: null,
                'Notifikasi DOKU dengan signature tidak valid ditolak untuk '.($invoice ?: 'invoice tidak dikenal'),
            );

            return response()->json(['success' => false, 'message' => 'Signature tidak valid'], 401);
        }

        $notificationKey = implode('|', [
            $invoice,
            $status,
            $amount ?? '-',
            $transaction['date'] ?? $body['transaction_date'] ?? '-',
        ]);

        try {
            $notification = PaymentNotification::create([
                'notification_key' => $notificationKey,
                'invoice_number' => $invoice ?: null,
                'order_number' => $orderNumber ?: null,
                'transaction_status' => $status ?: null,
                'amount' => $amount,
                'signature_valid' => true,
                'raw_payload' => $body,
            ]);
        } catch (QueryException $error) {
            if ($error->getCode() === '23000') {
                return response()->json(['success' => true, 'message' => 'Notifikasi duplikat diabaikan']);
            }
            throw $error;
        }

        $result = ['status' => 'IGNORED'];
        if (in_array($status, self::SUCCESS_STATES, true)) {
            $result = $this->payments->markPaid(
                $invoice,
                $amount,
                $body['channel']['id'] ?? $body['acquirer']['id'] ?? null,
                'DOKU_WEBHOOK',
                $body,
            );
        } elseif (in_array($status, self::EXPIRED_STATES, true)) {
            $this->payments->markFailed($invoice, 'EXPIRED');
            $result = ['status' => 'EXPIRED'];
        } elseif (in_array($status, self::FAILED_STATES, true)) {
            $this->payments->markFailed($invoice, 'FAILED');
            $result = ['status' => 'FAILED'];
        }

        $notification->update(['processed' => true, 'note' => json_encode($result)]);

        return response()->json(['success' => true, 'message' => 'Notifikasi diproses', 'data' => $result]);
    }

    /** Riwayat notifikasi pembayaran — untuk pemantauan admin. */
    public function notifications()
    {
        return $this->ok([
            'notifications' => PaymentNotification::latest('created_at')->take(50)->get(),
        ]);
    }
}
