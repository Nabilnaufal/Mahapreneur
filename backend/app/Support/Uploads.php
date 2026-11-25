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
        $directory = public_path('uploads/'.$folder);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Folder penyimpanan foto tidak dapat dibuat.');
        }

        $file->move($directory, $name);

        return 'uploads/'.$folder.'/'.$name;
    }
}
