<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['order.store', 'order.customer'])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('method'), fn ($query, string $method) => $query->where('method', $method))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'payments' => PaymentResource::collection($payments),
            'meta' => $this->meta($payments),
        ]);
    }
}
