<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Support\Activity;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function index()
    {
        return $this->ok([
            'banners' => BannerResource::collection(Banner::orderBy('sort_order')->get()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->bannerRules());
        $banner = Banner::create($data);
        Activity::log($request->user()->id, 'BANNER_CREATED', 'Banner', $banner->id, "Banner {$banner->title} dibuat");

        return $this->ok(['banner' => BannerResource::make($banner)], 'Banner ditambahkan');
    }

    public function update(Request $request, int $id)
    {
        $banner = Banner::find($id);
        if (! $banner) {
            throw ApiException::notFound('Banner tidak ditemukan');
        }

        $banner->update($request->validate($this->bannerRules(partial: true)));

        return $this->ok(['banner' => BannerResource::make($banner)], 'Banner diperbarui');
    }

    public function destroy(int $id)
    {
        Banner::whereKey($id)->delete();

        return $this->ok(null, 'Banner dihapus');
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:'.config('mahapreneur.max_upload_kb')]]);

        return $this->ok(['url' => Uploads::image($request->file('image'), 'banners')], 'Gambar diunggah');
    }

    private function bannerRules(bool $partial = false)
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$presence, 'string', 'min:3', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'image' => [$presence, 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }
}
