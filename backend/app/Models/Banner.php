<?php

namespace App\Models;

class Banner extends Model
{
    protected $guarded = [];

    protected $casts = ['sort_order' => 'integer'];
}
