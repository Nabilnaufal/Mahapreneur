<?php

namespace App\Models;

class Banner extends Model
{
    protected function casts()
    {
        return ['sort_order' => 'integer'];
    }
}
