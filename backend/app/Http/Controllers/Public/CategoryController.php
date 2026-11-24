<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => function ($query) {
                $query->visible();
            }])
            ->orderBy('sort_order')
            ->get();

        return $this->ok(['categories' => CategoryResource::collection($categories)]);
    }
}
