<?php

namespace App\Models;

class Banner extends Model
{
    protected $fillable = ['title', 'description', 'image', 'cta_label', 'cta_url', 'sort_order', 'status'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
