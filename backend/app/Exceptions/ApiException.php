<?php

namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        public int $status,
        string $message,
        public mixed $details = null,
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $message = 'Permintaan tidak valid', mixed $details = null): self
    {
        return new self(400, $message, $details);
    }

    public static function unauthorized(string $message = 'Silakan masuk terlebih dahulu'): self
    {
        return new self(401, $message);
    }

    public static function forbidden(string $message = 'Kamu tidak punya akses ke resource ini'): self
    {
        return new self(403, $message);
    }

    public static function notFound(string $message = 'Data tidak ditemukan'): self
    {
        return new self(404, $message);
    }

    public static function conflict(string $message = 'Data sudah ada'): self
    {
        return new self(409, $message);
    }
}
