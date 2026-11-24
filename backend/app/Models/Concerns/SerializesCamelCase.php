<?php

namespace App\Models\Concerns;

use DateTimeInterface;
use Illuminate\Support\Str;

/** Kolom database memakai snake_case, sedangkan API memakai camelCase. */
trait SerializesCamelCase
{
    public function toArray()
    {
        return collect(parent::toArray())
            ->mapWithKeys(fn ($value, $key) => [Str::camel($key) => $value])
            ->all();
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->toISOString();
    }
}
