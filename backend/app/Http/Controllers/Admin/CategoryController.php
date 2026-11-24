<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\Activity;
use App\Support\Reference;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->get();

        return $this->ok(['categories' => CategoryResource::collection($categories)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->categoryRules());
        $data['slug'] = Reference::slug($data['slug'] ?? $data['name'], 'kategori');
        $category = Category::create($data);
        Activity::log($request->user()->id, 'CATEGORY_CREATED', 'Category', $category->id, "Kategori {$category->name} dibuat");

        return $this->ok(['category' => CategoryResource::make($category)], 'Kategori ditambahkan');
    }

    public function update(Request $request, int $id)
    {
        $category = Category::find($id);
        if (! $category) {
            throw ApiException::notFound('Kategori tidak ditemukan');
        }

        $data = $request->validate($this->categoryRules(partial: true, ignoreId: $category->id));
        if (array_key_exists('slug', $data)) {
            $data['slug'] = blank($data['slug']) ? $category->slug : Reference::slug($data['slug'], 'kategori');
        }
        $category->update($data);

        return $this->ok(['category' => CategoryResource::make($category)], 'Kategori diperbarui');
    }

    public function destroy(Request $request, int $id)
    {
        $category = Category::find($id);
        if (! $category) {
            throw ApiException::notFound('Kategori tidak ditemukan');
        }
        $count = $category->products()->count();
        if ($count > 0) {
            throw ApiException::badRequest("Kategori masih dipakai {$count} produk. Pindahkan produk terlebih dahulu.");
        }
        $category->delete();
        Activity::log($request->user()->id, 'CATEGORY_DELETED', 'Category', $id, 'Kategori dihapus');

        return $this->ok(null, 'Kategori dihapus');
    }

    private function categoryRules(bool $partial = false, ?int $ignoreId = null)
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'string', 'min:2', 'max:40'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:40', Rule::unique('categories', 'slug')->ignore($ignoreId)],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
