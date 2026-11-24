<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Frontend mengirim camelCase; backend bekerja dengan snake_case ala Laravel. */
class NormalizeInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->replace($this->keysToSnake($request->all()));

        return $next($request);
    }

    private function keysToSnake(array $input)
    {
        $result = [];
        foreach ($input as $key => $value) {
            $result[is_string($key) ? Str::snake($key) : $key] = is_array($value) ? $this->keysToSnake($value) : $value;
        }

        return $result;
    }
}
