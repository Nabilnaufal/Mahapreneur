<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function perPage(Request $request, int $default = 12, int $max = 60): int
    {
        return min($max, max(1, (int) $request->query('limit', $default)));
    }

    protected function meta(LengthAwarePaginator $paginator)
    {
        return [
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'limit' => $paginator->perPage(),
            'totalPages' => max(1, $paginator->lastPage()),
        ];
    }
}
