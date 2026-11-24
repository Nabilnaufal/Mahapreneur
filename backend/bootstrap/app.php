<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureStore;
use App\Http\Middleware\NormalizeInput;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [NormalizeInput::class]);
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'auth.api' => Authenticate::class,
            'role' => EnsureRole::class,
            'store' => EnsureStore::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ApiException $error, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'errors' => $error->details,
            ], $error->status);
        });

        $exceptions->render(function (ValidationException $error, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = collect($error->errors())
                ->flatMap(fn ($messages, $field) => array_map(
                    fn ($message) => ['field' => Str::camel($field), 'message' => $message],
                    $messages,
                ))
                ->values();

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $errors,
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $error, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'message' => 'Silakan masuk terlebih dahulu'], 401);
        });

        $exceptions->render(function (NotFoundHttpException $error, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        });

        $exceptions->render(function (Throwable $error, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            Log::error($error);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $error->getMessage() : 'Terjadi kesalahan pada server',
            ], 500);
        });
    })->create();
