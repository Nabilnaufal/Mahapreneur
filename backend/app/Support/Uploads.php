<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Uploads
{
    /** Simpan satu gambar ke public/uploads/{folder} dan kembalikan path relatifnya. */
    public static function image(UploadedFile $file, string $folder): string
    {
        $name = (string) Str::ulid().'.'.$file->extension();
        $file->move(public_path('uploads/'.$folder), $name);

        return 'uploads/'.$folder.'/'.$name;
    }
}
