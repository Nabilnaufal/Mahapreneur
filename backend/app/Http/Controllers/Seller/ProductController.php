<?php

namespace App\Http\Controllers\Seller;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Support\Activity;
use App\Support\Reference;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('store_id', $this->storeId($request))
            ->withCard()
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where('name', 'like', "%{$keyword}%"))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'products' => ProductResource::collection($products),
            'meta' => $this->meta($products),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);

        return $this->ok(['product' => ProductResource::make($product)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->productRules());
        $category = Category::whereKey($data['category_id'])->where('is_active', true)->first();
        if (! $category) {
            throw ApiException::badRequest('Kategori tidak valid');
        }

        $slug = $this->uniqueProductSlug($data['name']);
        $productData = $data;
        $productData['store_id'] = $this->storeId($request);
        $productData['slug'] = $slug;
        $productData['service_terms'] = $data['service_terms'] ?? null;
        $productData['service_duration'] = $data['service_duration'] ?? null;
        $product = Product::create($productData)->load(['images', 'category', 'store']);

        Activity::log($request->user()->id, 'PRODUCT_CREATED', 'Product', $product->id, "Produk {$product->name} ditambahkan");

        return $this->ok(['product' => ProductResource::make($product)], 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);
        $data = $request->validate($this->productRules(partial: true));

        if (isset($data['category_id'])) {
            $category = Category::whereKey($data['category_id'])->where('is_active', true)->first();
            if (! $category) {
                throw ApiException::badRequest('Kategori tidak valid');
            }
        }

        $product->update($data);
        $product->load(['images', 'category', 'store']);

        return $this->ok(['product' => ProductResource::make($product)], 'Produk diperbarui');
    }

    public function destroy(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);
        $product->delete();
        Activity::log($request->user()->id, 'PRODUCT_DELETED', 'Product', $product->id, "Produk {$product->name} dihapus");

        return $this->ok(null, 'Produk dihapus');
    }

    public function uploadImages(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,webp,gif', 'max:'.config('mahapreneur.max_upload_kb')],
        ], [
            'images.required' => 'Pilih minimal satu foto produk.',
            'images.array' => 'Format unggahan foto tidak valid.',
            'images.min' => 'Pilih minimal satu foto produk.',
            'images.*.image' => 'Setiap file harus berupa gambar.',
            'images.*.mimes' => 'Format foto harus JPG, PNG, WEBP, atau GIF.',
            'images.*.max' => 'Ukuran setiap foto maksimal '.(config('mahapreneur.max_upload_kb') / 1024).' MB.',
        ]);

        $files = $request->file('images');
        if ($product->images()->count() + count($files) > 5) {
            throw ApiException::badRequest('Foto tidak dapat diunggah. Maksimal 5 foto per produk.');
        }

        $start = $product->images()->count();
        foreach ($files as $index => $file) {
            $product->images()->create([
                'url' => Uploads::image($file, 'products'),
                'sort_order' => $start + $index,
            ]);
        }

        $product->load(['images', 'category', 'store']);

        return $this->ok(['product' => ProductResource::make($product)], 'Gambar produk diunggah');
    }

    public function deleteImage(Request $request, int $id, int $imageId)
    {
        $product = $this->findProduct($request, $id);
        $product->images()->whereKey($imageId)->delete();
        $product->load(['images', 'category', 'store']);

        return $this->ok(['product' => ProductResource::make($product)], 'Gambar dihapus');
    }

    private function storeId(Request $request): int
    {
        return $request->user()->store->id;
    }

    private function findProduct(Request $request, int $id): Product
    {
        $product = Product::where('store_id', $this->storeId($request))->withCard()->find($id);
        if (! $product) {
            throw ApiException::notFound('Produk tidak ditemukan');
        }

        return $product;
    }

    private function uniqueProductSlug(string $name): string
    {
        $slug = Reference::slug($name, 'produk');
        while (Product::where('slug', $slug)->exists()) {
            $slug = Reference::slug($name.'-'.Str::lower(Str::random(4)), 'produk');
        }

        return $slug;
    }

    private function productRules(bool $partial = false)
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'string', 'min:3', 'max:120'],
            'description' => [$presence, 'string', 'min:10'],
            'price' => [$presence, 'integer', 'min:0', 'max:2000000000'],
            'stock' => [$presence, 'integer', 'min:0', 'max:1000000'],
            'category_id' => [$presence, 'integer', Rule::exists('categories', 'id')],
            'type' => ['sometimes', Rule::in(['GOODS', 'SERVICE'])],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
            'service_terms' => ['nullable', 'string', 'max:1000'],
            'service_duration' => ['nullable', 'string', 'max:80'],
        ];
    }
}
